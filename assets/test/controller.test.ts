import {Application, type Controller} from '@hotwired/stimulus';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const mocks = vi.hoisted(() => {
    const instance = {
        drive: vi.fn(),
        highlight: vi.fn(),
        destroy: vi.fn(),
        moveNext: vi.fn(),
        movePrevious: vi.fn(),
        moveTo: vi.fn(),
        refresh: vi.fn(),
    };

    return {
        ...instance,
        instance,
        driver: vi.fn(() => instance),
    };
});

vi.mock('driver.js', () => ({driver: mocks.driver}));

const {default: TourController} = await import('../src/controller.js');

const IDENTIFIER = 'pentiminax--ux-driver--tour';

const tick = (): Promise<void> => new Promise((resolve) => setTimeout(resolve, 0));

let application: Application;

async function mount(html: string): Promise<HTMLElement> {
    document.body.innerHTML = html;

    application = new Application(document.documentElement);
    application.register(IDENTIFIER, TourController);
    await application.start();
    await tick();

    return document.querySelector<HTMLElement>(`[data-controller="${IDENTIFIER}"]`)!;
}

function controllerFor(element: HTMLElement): Controller {
    return application.getControllerForElementAndIdentifier(element, IDENTIFIER)!;
}

function tour(attributes = '', steps = STEPS): string {
    return `<div data-controller="${IDENTIFIER}"
                 data-${IDENTIFIER}-id-value="onboarding"
                 data-${IDENTIFIER}-options-value='{"showProgress":true,"animate":false}'
                 ${attributes}>${steps}</div>`;
}

const STEPS = `
    <div data-${IDENTIFIER}-target="step" data-step-order="2" data-step-title="Sidebar"
         data-step-description="Navigation" data-step-side="right" data-step-align="end"></div>
    <div data-${IDENTIFIER}-target="step" data-step-order="1" data-step-title="Header"
         data-step-description="Top bar" data-step-side="bottom" data-step-align="start"></div>
`;

type Hook = (element: Element | undefined, step: object, opts: object) => void;

/** Invokes a driver.js hook the controller registered, as driver.js itself would. */
function invokeHook(name: string, index = 0): void {
    const hook = (mocks.driver.mock.calls[0]![0] as Record<string, Hook>)[name]!;

    hook(document.body, {popover: {title: 'Header'}}, {
        config: {},
        state: {},
        driver: mocks.instance,
        index,
    });
}

function actionEvent(params: Record<string, unknown>): Event {
    return Object.assign(new Event('click'), {params});
}

describe('tour controller', () => {
    beforeEach(() => {
        localStorage.clear();
        mocks.driver.mockClear();
        Object.values(mocks.instance).forEach((mock) => mock.mockClear());
    });

    afterEach(() => {
        application?.stop();
        document.body.innerHTML = '';
    });

    it('builds driver.js with merged options and drives the tour', async () => {
        const element = await mount(tour());

        expect(mocks.driver).not.toHaveBeenCalled();

        (controllerFor(element) as unknown as {start(): void}).start();

        expect(mocks.driver).toHaveBeenCalledTimes(1);

        const config = mocks.driver.mock.calls[0]![0] as Record<string, unknown>;

        expect(config.showProgress).toBe(true);
        expect(config.animate).toBe(false);
        expect(config.steps).toHaveLength(2);
        expect(mocks.drive).toHaveBeenCalledTimes(1);
    });

    it('orders declarative step targets by data-step-order', async () => {
        const element = await mount(tour());

        (controllerFor(element) as unknown as {start(): void}).start();

        const config = mocks.driver.mock.calls[0]![0] as {
            steps: Array<{element: HTMLElement; popover: Record<string, string>}>;
        };

        expect(config.steps.map((step) => step.popover.title)).toEqual(['Header', 'Sidebar']);
        expect(config.steps.map((step) => step.element.dataset.stepOrder)).toEqual(['1', '2']);
        expect(config.steps[0]!.popover).toMatchObject({
            title: 'Header',
            description: 'Top bar',
            side: 'bottom',
            align: 'start',
        });
    });

    it('highlights the first step with its element and popover', async () => {
        const element = await mount(tour());

        (controllerFor(element) as unknown as {highlight(): void}).highlight();

        expect(mocks.driver).toHaveBeenCalledTimes(1);
        expect(mocks.driver.mock.calls[0]![0]).not.toHaveProperty('steps');
        expect(mocks.highlight).toHaveBeenCalledTimes(1);

        const argument = mocks.highlight.mock.calls[0]![0] as {
            element: HTMLElement;
            popover: Record<string, string>;
        };

        expect(argument.element.dataset.stepOrder).toBe('1');
        expect(argument.popover).toMatchObject({title: 'Header', side: 'bottom', align: 'start'});
        expect(mocks.drive).not.toHaveBeenCalled();
    });

    it('starts on connect when autostart is enabled', async () => {
        await mount(tour(`data-${IDENTIFIER}-autostart-value="true"`));

        expect(mocks.driver).toHaveBeenCalledTimes(1);
        expect(mocks.drive).toHaveBeenCalledTimes(1);
    });

    it('does not autostart when the once flag is already stored', async () => {
        localStorage.setItem('ux-driver:seen:onboarding', '1');

        await mount(
            tour(`data-${IDENTIFIER}-autostart-value="true" data-${IDENTIFIER}-once-value="true"`),
        );

        expect(mocks.driver).not.toHaveBeenCalled();
        expect(mocks.drive).not.toHaveBeenCalled();
    });

    it('persists the once flag under ux-driver:seen:<id>', async () => {
        const element = await mount(tour(`data-${IDENTIFIER}-once-value="true"`));
        const controller = controllerFor(element) as unknown as {start(): void};

        expect(localStorage.getItem('ux-driver:seen:onboarding')).toBeNull();

        controller.start();

        expect(localStorage.getItem('ux-driver:seen:onboarding')).toBe('1');

        controller.start();

        expect(mocks.drive).toHaveBeenCalledTimes(1);
    });

    it('destroys the driver instance on disconnect', async () => {
        const element = await mount(tour(`data-${IDENTIFIER}-autostart-value="true"`));

        expect(mocks.destroy).not.toHaveBeenCalled();

        element.remove();
        await tick();

        expect(mocks.destroy).toHaveBeenCalledTimes(1);
    });

    it('destroys the previous instance when start is called twice', async () => {
        const element = await mount(tour());
        const controller = controllerFor(element) as unknown as {start(): void};

        controller.start();
        controller.start();

        expect(mocks.driver).toHaveBeenCalledTimes(2);
        expect(mocks.destroy).toHaveBeenCalledTimes(1);
        expect(mocks.drive).toHaveBeenCalledTimes(2);
    });

    it('destroys the running tour when switching from start to highlight', async () => {
        const element = await mount(tour());
        const controller = controllerFor(element) as unknown as {
            start(): void;
            highlight(): void;
        };

        controller.start();
        controller.highlight();

        expect(mocks.driver).toHaveBeenCalledTimes(2);
        expect(mocks.destroy).toHaveBeenCalledTimes(1);
        expect(mocks.highlight).toHaveBeenCalledTimes(1);
    });

    it('emits ux-driver:empty and builds nothing when no step resolves', async () => {
        const element = await mount(tour(`data-${IDENTIFIER}-once-value="true"`, ''));
        const empty = vi.fn();

        element.addEventListener('ux-driver:empty', empty);

        (controllerFor(element) as unknown as {start(): void}).start();

        expect(mocks.driver).not.toHaveBeenCalled();
        expect(mocks.drive).not.toHaveBeenCalled();
        expect(empty).toHaveBeenCalledTimes(1);
        expect((empty.mock.calls[0]![0] as CustomEvent).detail).toMatchObject({id: 'onboarding'});
        expect(localStorage.getItem('ux-driver:seen:onboarding')).toBeNull();
    });

    it('emits ux-driver:empty when highlight finds no step', async () => {
        const element = await mount(tour('', ''));
        const empty = vi.fn();

        element.addEventListener('ux-driver:empty', empty);

        (controllerFor(element) as unknown as {highlight(): void}).highlight();

        expect(mocks.driver).not.toHaveBeenCalled();
        expect(mocks.highlight).not.toHaveBeenCalled();
        expect(empty).toHaveBeenCalledTimes(1);
    });

    it('still autostarts a once tour when localStorage is unavailable', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('SecurityError');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('SecurityError');
        });

        await mount(
            tour(`data-${IDENTIFIER}-autostart-value="true" data-${IDENTIFIER}-once-value="true"`),
        );

        expect(mocks.drive).toHaveBeenCalledTimes(1);

        vi.restoreAllMocks();
    });

    it.each([
        ['onHighlightStarted', 'ux-driver:highlight-started'],
        ['onHighlighted', 'ux-driver:highlighted'],
        ['onDeselected', 'ux-driver:deselected'],
        ['onDestroyed', 'ux-driver:destroyed'],
    ])('bridges %s to %s', async (hook, eventName) => {
        const element = await mount(tour());
        const listener = vi.fn();

        element.addEventListener(eventName, listener);
        (controllerFor(element) as unknown as {start(): void}).start();

        invokeHook(hook, 1);

        expect(listener).toHaveBeenCalledTimes(1);
        expect((listener.mock.calls[0]![0] as CustomEvent).detail).toMatchObject({
            tourId: 'onboarding',
            index: 1,
            element: document.body,
            driver: mocks.instance,
        });
    });

    it.each([
        ['onNextClick', 'ux-driver:next', 'moveNext'],
        ['onPrevClick', 'ux-driver:previous', 'movePrevious'],
        ['onCloseClick', 'ux-driver:close', 'destroy'],
        ['onDoneClick', 'ux-driver:done', 'destroy'],
        // driver.js aborts its own teardown as soon as onDestroyStarted is supplied: without
        // this explicit destroy() the overlay could never be closed again.
        ['onDestroyStarted', 'ux-driver:destroy-started', 'destroy'],
    ] as const)('keeps the default behaviour of %s while emitting %s', async (hook, eventName, method) => {
        const element = await mount(tour());
        const listener = vi.fn();

        element.addEventListener(eventName, listener);
        (controllerFor(element) as unknown as {start(): void}).start();

        invokeHook(hook);

        expect(listener).toHaveBeenCalledTimes(1);
        expect((listener.mock.calls[0]![0] as CustomEvent).cancelable).toBe(true);
        expect(mocks.instance[method]).toHaveBeenCalledTimes(1);
    });

    it.each([
        ['onNextClick', 'ux-driver:next', 'moveNext'],
        ['onPrevClick', 'ux-driver:previous', 'movePrevious'],
        ['onCloseClick', 'ux-driver:close', 'destroy'],
        ['onDoneClick', 'ux-driver:done', 'destroy'],
        ['onDestroyStarted', 'ux-driver:destroy-started', 'destroy'],
    ] as const)('suppresses %s when a listener calls preventDefault', async (hook, eventName, method) => {
        const element = await mount(tour());

        element.addEventListener(eventName, (event) => event.preventDefault());
        (controllerFor(element) as unknown as {start(): void}).start();

        invokeHook(hook);

        expect(mocks.instance[method]).not.toHaveBeenCalled();
    });

    it('drives from the index passed as an action param', async () => {
        const element = await mount(tour());

        (controllerFor(element) as unknown as {start(event: Event): void}).start(
            actionEvent({index: 1}),
        );

        expect(mocks.drive).toHaveBeenCalledWith(1);
    });

    it('drives from the first step when no index param is given', async () => {
        const element = await mount(tour());

        (controllerFor(element) as unknown as {start(event: Event): void}).start(actionEvent({}));

        expect(mocks.drive).toHaveBeenCalledWith(undefined);
    });

    it('exposes navigation actions that delegate to the running instance', async () => {
        const element = await mount(tour());
        const controller = controllerFor(element) as unknown as {
            start(): void;
            next(): void;
            previous(): void;
            refresh(): void;
            moveTo(event: Event): void;
            destroy(): void;
        };

        controller.start();
        controller.next();
        controller.previous();
        controller.refresh();
        controller.moveTo(actionEvent({index: 2}));
        controller.destroy();

        expect(mocks.moveNext).toHaveBeenCalledTimes(1);
        expect(mocks.movePrevious).toHaveBeenCalledTimes(1);
        expect(mocks.refresh).toHaveBeenCalledTimes(1);
        expect(mocks.moveTo).toHaveBeenCalledWith(2);
        expect(mocks.destroy).toHaveBeenCalledTimes(1);
    });

    it('ignores navigation actions when no tour is running', async () => {
        const element = await mount(tour());
        const controller = controllerFor(element) as unknown as {
            next(): void;
            previous(): void;
            refresh(): void;
            moveTo(event: Event): void;
            destroy(): void;
        };

        controller.next();
        controller.previous();
        controller.refresh();
        controller.moveTo(actionEvent({index: 2}));
        controller.destroy();

        expect(mocks.moveNext).not.toHaveBeenCalled();
        expect(mocks.movePrevious).not.toHaveBeenCalled();
        expect(mocks.refresh).not.toHaveBeenCalled();
        expect(mocks.moveTo).not.toHaveBeenCalled();
        expect(mocks.destroy).not.toHaveBeenCalled();
    });

    it('ignores moveTo without a usable index param', async () => {
        const element = await mount(tour());
        const controller = controllerFor(element) as unknown as {
            start(): void;
            moveTo(event: Event): void;
        };

        controller.start();
        controller.moveTo(actionEvent({}));

        expect(mocks.moveTo).not.toHaveBeenCalled();
    });

    it('stays idempotent when disconnect runs twice', async () => {
        const element = await mount(tour(`data-${IDENTIFIER}-autostart-value="true"`));
        const controller = controllerFor(element) as unknown as {disconnect(): void};

        controller.disconnect();
        controller.disconnect();

        expect(mocks.destroy).toHaveBeenCalledTimes(1);
    });
});
