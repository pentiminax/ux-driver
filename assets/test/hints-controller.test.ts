import {Application, type Controller} from '@hotwired/stimulus';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const mocks = vi.hoisted(() => {
    const instance = {
        show: vi.fn(),
        hide: vi.fn(),
        open: vi.fn(),
        close: vi.fn(),
        dismiss: vi.fn(),
        restore: vi.fn(),
        refresh: vi.fn(),
        getHints: vi.fn(() => [] as Array<{id?: string}>),
    };

    return {...instance, instance, hints: vi.fn(() => instance)};
});

vi.mock('driver.js/hints', () => ({hints: mocks.hints}));

const {default: HintsController} = await import('../src/hints-controller.js');

const IDENTIFIER = 'pentiminax--ux-driver--hints';

const tick = (): Promise<void> => new Promise((resolve) => setTimeout(resolve, 0));

let application: Application;

async function mount(html: string): Promise<HTMLElement> {
    document.body.innerHTML = html;

    application = new Application(document.documentElement);
    application.register(IDENTIFIER, HintsController);
    await application.start();
    await tick();

    return document.querySelector<HTMLElement>(`[data-controller="${IDENTIFIER}"]`)!;
}

function controllerFor(element: HTMLElement): Controller {
    return application.getControllerForElementAndIdentifier(element, IDENTIFIER)!;
}

const HINTS = `
    <button data-${IDENTIFIER}-target="hint" data-hint-id="export" data-hint-title="Exporter"
            data-hint-side="bottom" data-hint-align="start"></button>
    <button data-${IDENTIFIER}-target="hint" data-hint-id="filters" data-hint-title="Filtres"
            data-hint-side="right" data-hint-align="end"></button>
`;

function group(attributes = '', children = HINTS): string {
    return `<div data-controller="${IDENTIFIER}"
                 data-${IDENTIFIER}-id-value="help"
                 data-${IDENTIFIER}-options-value='{"overlay":true,"buttonText":"Compris"}'
                 ${attributes}>${children}</div>`;
}

type Hook = (element: Element | undefined, hint: object) => void;

/** Invokes a driver.js hook the controller registered, as driver.js itself would. */
function invokeHook(name: string, hint: object = {id: 'export', data: {tracking: 'export'}}): void {
    (mocks.hints.mock.calls[0]![0] as unknown as Record<string, Hook>)[name]!(document.body, hint);
}

function actionEvent(params: Record<string, unknown>): Event {
    return Object.assign(new Event('click'), {params});
}

type Actions = {
    show(): void;
    hide(): void;
    open(event: Event): void;
    close(): void;
    dismiss(event: Event): void;
    restore(event: Event): void;
    restoreAll(): void;
    refresh(): void;
    disconnect(): void;
};

describe('hints controller', () => {
    beforeEach(() => {
        mocks.hints.mockClear();
        Object.values(mocks.instance).forEach((mock) => mock.mockClear());
        mocks.getHints.mockReturnValue([]);
    });

    afterEach(() => {
        application?.stop();
        document.body.innerHTML = '';
    });

    it('shows the hints on connect with the merged options', async () => {
        await mount(group());

        expect(mocks.hints).toHaveBeenCalledTimes(1);

        const config = mocks.hints.mock.calls[0]![0] as unknown as {
            overlay: boolean;
            buttonText: string;
            hints: Array<{popover: Record<string, string>}>;
        };

        expect(config.overlay).toBe(true);
        expect(config.buttonText).toBe('Compris');
        expect(config.hints.map((hint) => hint.popover.title)).toEqual(['Exporter', 'Filtres']);
        expect(mocks.show).toHaveBeenCalledTimes(1);
    });

    it('does not show anything on connect when autostart is disabled', async () => {
        await mount(group(`data-${IDENTIFIER}-autostart-value="false"`));

        expect(mocks.hints).not.toHaveBeenCalled();
        expect(mocks.show).not.toHaveBeenCalled();
    });

    it('prefers the hints declared by the builder over the DOM targets', async () => {
        await mount(
            group(
                `data-${IDENTIFIER}-hints-value='[{"element":".export","popover":{"title":"Exporter (PHP)"}}]'`,
            ),
        );

        const config = mocks.hints.mock.calls[0]![0] as unknown as {
            hints: Array<{element: string; popover: Record<string, string>}>;
        };

        expect(config.hints).toEqual([{element: '.export', popover: {title: 'Exporter (PHP)'}}]);
    });

    it('emits ux-driver:empty and builds nothing when no hint resolves', async () => {
        const empty = vi.fn();

        document.documentElement.addEventListener('ux-driver:empty', empty);
        await mount(group(`data-${IDENTIFIER}-autostart-value="false"`, ''));

        const element = document.querySelector<HTMLElement>(`[data-controller="${IDENTIFIER}"]`)!;

        (controllerFor(element) as unknown as Actions).show();

        expect(mocks.hints).not.toHaveBeenCalled();
        expect(empty).toHaveBeenCalledTimes(1);
        expect((empty.mock.calls[0]![0] as CustomEvent).detail).toMatchObject({id: 'help'});

        document.documentElement.removeEventListener('ux-driver:empty', empty);
    });

    it('hides the previous group when show is called twice', async () => {
        const element = await mount(group());

        (controllerFor(element) as unknown as Actions).show();

        expect(mocks.hints).toHaveBeenCalledTimes(2);
        expect(mocks.hide).toHaveBeenCalledTimes(1);
        expect(mocks.show).toHaveBeenCalledTimes(2);
    });

    it('hides the running group on disconnect', async () => {
        const element = await mount(group());

        expect(mocks.hide).not.toHaveBeenCalled();

        element.remove();
        await tick();

        expect(mocks.hide).toHaveBeenCalledTimes(1);
    });

    it('stays idempotent when disconnect runs twice', async () => {
        const element = await mount(group());
        const controller = controllerFor(element) as unknown as Actions;

        controller.disconnect();
        controller.disconnect();

        expect(mocks.hide).toHaveBeenCalledTimes(1);
    });

    it('exposes actions that delegate to the running group', async () => {
        const element = await mount(group());
        const controller = controllerFor(element) as unknown as Actions;

        controller.open(actionEvent({hintId: 'export'}));
        controller.dismiss(actionEvent({hintId: 'filters'}));
        controller.restore(actionEvent({hintId: 'export'}));
        controller.close();
        controller.refresh();

        expect(mocks.open).toHaveBeenCalledWith('export');
        expect(mocks.dismiss).toHaveBeenCalledWith('filters');
        expect(mocks.restore).toHaveBeenCalledWith('export');
        expect(mocks.close).toHaveBeenCalledTimes(1);
        expect(mocks.refresh).toHaveBeenCalledTimes(1);
    });

    it('ignores the id-bound actions without a usable hintId param', async () => {
        const element = await mount(group());
        const controller = controllerFor(element) as unknown as Actions;

        controller.open(actionEvent({}));
        controller.dismiss(actionEvent({hintId: ''}));
        controller.restore(actionEvent({}));

        expect(mocks.open).not.toHaveBeenCalled();
        expect(mocks.dismiss).not.toHaveBeenCalled();
        expect(mocks.restore).not.toHaveBeenCalled();
    });

    it('ignores the actions when no group is running', async () => {
        const element = await mount(group(`data-${IDENTIFIER}-autostart-value="false"`));
        const controller = controllerFor(element) as unknown as Actions;

        controller.open(actionEvent({hintId: 'export'}));
        controller.close();
        controller.restoreAll();
        controller.refresh();

        expect(mocks.open).not.toHaveBeenCalled();
        expect(mocks.close).not.toHaveBeenCalled();
        expect(mocks.restore).not.toHaveBeenCalled();
        expect(mocks.refresh).not.toHaveBeenCalled();
    });

    /** driver.js has no bulk restore, and a hint declared without an id answers to its index. */
    it('restores every hint, falling back to the index of the unnamed ones', async () => {
        mocks.getHints.mockReturnValue([{id: 'export'}, {}]);

        const element = await mount(group());

        (controllerFor(element) as unknown as Actions).restoreAll();

        expect(mocks.restore.mock.calls).toEqual([['export'], [1]]);
    });

    it.each([
        ['onOpen', 'ux-driver:hint-open'],
        ['onDismiss', 'ux-driver:hint-dismiss'],
        ['onButtonClick', 'ux-driver:hint-button-click'],
    ])('bridges %s to %s', async (hook, eventName) => {
        const element = await mount(group());
        const listener = vi.fn();

        element.addEventListener(eventName, listener);
        invokeHook(hook);

        expect(listener).toHaveBeenCalledTimes(1);
        expect((listener.mock.calls[0]![0] as CustomEvent).detail).toMatchObject({
            groupId: 'help',
            hintId: 'export',
            element: document.body,
            data: {tracking: 'export'},
            hints: mocks.instance,
        });
    });
});
