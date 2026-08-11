import {Controller} from '@hotwired/stimulus';
import {driver, type Config, type DriveStep, type Popover} from 'driver.js';
import {alreadySeen, markSeen, resolveSteps} from './tour-utils.js';

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

    private instance: ReturnType<typeof driver> | null = null;

    connect(): void {
        if (this.autostartValue && !alreadySeen(this.idValue, this.onceValue)) {
            this.start();
        }
    }

    start(): void {
        if (this.onceValue && alreadySeen(this.idValue, this.onceValue)) {
            return;
        }

        const steps = resolveSteps(this.stepsValue, this.stepTargets);

        if (steps.length === 0) {
            this.dispatch('empty', {detail: {id: this.idValue}, prefix: 'ux-driver'});

            return;
        }

        const config: Config = {...this.optionsValue, steps};

        this.dispatch('pre-connect', {detail: {config}, prefix: 'ux-driver'});

        this.teardown();
        this.instance = driver(config);
        this.dispatch('connect', {detail: {driver: this.instance}, prefix: 'ux-driver'});

        this.instance.drive();
        markSeen(this.idValue, this.onceValue);
    }

    highlight(): void {
        const steps = resolveSteps(this.stepsValue, this.stepTargets);
        const step = steps[0];

        if (!step) {
            this.dispatch('empty', {detail: {id: this.idValue}, prefix: 'ux-driver'});

            return;
        }

        const config: Config = {...this.optionsValue};

        this.dispatch('pre-connect', {detail: {config, step}, prefix: 'ux-driver'});

        this.teardown();
        this.instance = driver(config);
        this.dispatch('connect', {detail: {driver: this.instance}, prefix: 'ux-driver'});

        this.instance.highlight({
            element: step.element,
            popover: step.popover as Popover,
        });
    }

    disconnect(): void {
        this.teardown();
    }

    private teardown(): void {
        this.instance?.destroy();
        this.instance = null;
    }
}
