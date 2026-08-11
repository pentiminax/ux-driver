import {Controller, type ActionEvent} from '@hotwired/stimulus';
import {
    driver,
    type Config,
    type Driver,
    type DriveStep,
    type DriverHook,
    type Popover,
} from 'driver.js';
import {alreadySeen, markSeen, resolveSteps} from './tour-utils.js';

/** driver.js declares HookOpts but does not export it. */
type HookOpts = Parameters<DriverHook>[2];

export default class extends Controller {
    static targets = ['step'];

    static values = {
        id: String,
        steps: {type: Array, default: []},
        options: {type: Object, default: {}},
        autostart: {type: Boolean, default: false},
        once: {type: Boolean, default: false},
    };

    declare readonly stepTargets: HTMLElement[];
    declare readonly idValue: string;
    declare readonly stepsValue: DriveStep[];
    declare readonly optionsValue: Config;
    declare readonly autostartValue: boolean;
    declare readonly onceValue: boolean;

    private instance: Driver | null = null;

    connect(): void {
        if (this.autostartValue && !alreadySeen(this.idValue, this.onceValue)) {
            this.start();
        }
    }

    start(event?: ActionEvent): void {
        if (this.onceValue && alreadySeen(this.idValue, this.onceValue)) {
            return;
        }

        const steps = resolveSteps(this.stepsValue, this.stepTargets);

        if (steps.length === 0) {
            this.dispatch('empty', {detail: {id: this.idValue}, prefix: 'ux-driver'});

            return;
        }

        const config: Config = {...this.optionsValue, ...this.hooks(), steps};

        this.dispatch('pre-connect', {detail: {config}, prefix: 'ux-driver'});

        this.teardown();
        this.instance = driver(config);
        this.dispatch('connect', {detail: {driver: this.instance}, prefix: 'ux-driver'});

        this.instance.drive(this.indexParam(event));
        markSeen(this.idValue, this.onceValue);
    }

    highlight(): void {
        const steps = resolveSteps(this.stepsValue, this.stepTargets);
        const step = steps[0];

        if (!step) {
            this.dispatch('empty', {detail: {id: this.idValue}, prefix: 'ux-driver'});

            return;
        }

        const config: Config = {...this.optionsValue, ...this.hooks()};

        this.dispatch('pre-connect', {detail: {config, step}, prefix: 'ux-driver'});

        this.teardown();
        this.instance = driver(config);
        this.dispatch('connect', {detail: {driver: this.instance}, prefix: 'ux-driver'});

        this.instance.highlight({
            element: step.element,
            popover: step.popover as Popover,
        });
    }

    next(): void {
        this.instance?.moveNext();
    }

    previous(): void {
        this.instance?.movePrevious();
    }

    moveTo(event: ActionEvent): void {
        const index = this.indexParam(event);

        if (index !== undefined) {
            this.instance?.moveTo(index);
        }
    }

    refresh(): void {
        this.instance?.refresh();
    }

    destroy(): void {
        this.teardown();
    }

    disconnect(): void {
        this.teardown();
    }

    private teardown(): void {
        this.instance?.destroy();
        this.instance = null;
    }

    private indexParam(event?: ActionEvent): number | undefined {
        const index = Number(event?.params?.index);

        return Number.isInteger(index) ? index : undefined;
    }

    /**
     * driver.js only takes callbacks, which PHP and Twig cannot serialize. Every hook is
     * bridged to a Stimulus event so a tour declared server-side stays observable.
     *
     * Beware: driver.js *replaces* its own behaviour with the callback it is given. Supplying
     * onNextClick/onPrevClick/onCloseClick/onDoneClick removes the default navigation, and
     * onDestroyStarted aborts the teardown entirely — an overlay the user could no longer
     * close. The click hooks therefore call back into the driver themselves, unless a listener
     * called preventDefault() on the (cancelable) event.
     */
    private hooks(): Config {
        return {
            onHighlightStarted: this.observe('highlight-started'),
            onHighlighted: this.observe('highlighted'),
            onDeselected: this.observe('deselected'),
            onDestroyed: this.observe('destroyed'),
            onNextClick: this.intercept('next', (instance) => instance.moveNext()),
            onPrevClick: this.intercept('previous', (instance) => instance.movePrevious()),
            onCloseClick: this.intercept('close', (instance) => instance.destroy()),
            onDoneClick: this.intercept('done', (instance) => instance.destroy()),
            onDestroyStarted: this.intercept('destroy-started', (instance) => instance.destroy()),
        };
    }

    private observe(name: string): DriverHook {
        return (element, step, opts) => {
            this.dispatch(name, {detail: this.hookDetail(element, step, opts), prefix: 'ux-driver'});
        };
    }

    private intercept(name: string, action: (instance: Driver) => void): DriverHook {
        return (element, step, opts) => {
            const event = this.dispatch(name, {
                detail: this.hookDetail(element, step, opts),
                prefix: 'ux-driver',
                cancelable: true,
            });

            if (!event.defaultPrevented) {
                action(opts.driver);
            }
        };
    }

    private hookDetail(
        element: Element | undefined,
        step: DriveStep,
        opts: HookOpts,
    ): Record<string, unknown> {
        return {
            tourId: this.idValue,
            index: opts.index,
            step,
            element,
            driver: opts.driver,
        };
    }
}
