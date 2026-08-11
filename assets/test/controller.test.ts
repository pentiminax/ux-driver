import {Application, type Controller} from '@hotwired/stimulus';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const mocks = vi.hoisted(() => {
    const drive = vi.fn();
    const highlight = vi.fn();
    const destroy = vi.fn();

    return {
        drive,
        highlight,
        destroy,
        driver: vi.fn(() => ({drive, highlight, destroy})),
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

describe('tour controller', () => {
    beforeEach(() => {
        localStorage.clear();
        mocks.driver.mockClear();
        mocks.drive.mockClear();
        mocks.highlight.mockClear();
        mocks.destroy.mockClear();
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

    it('stays idempotent when disconnect runs twice', async () => {
        const element = await mount(tour(`data-${IDENTIFIER}-autostart-value="true"`));
        const controller = controllerFor(element) as unknown as {disconnect(): void};

        controller.disconnect();
        controller.disconnect();

        expect(mocks.destroy).toHaveBeenCalledTimes(1);
    });
});
