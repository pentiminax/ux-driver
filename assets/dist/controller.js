import { Controller } from '@hotwired/stimulus';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { alreadySeen, markSeen, resolveSteps } from './tour-utils.js';
class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.instance = null;
    }
    connect() {
        if (this.autostartValue && !alreadySeen(this.idValue, this.onceValue)) {
            this.start();
        }
    }
    start() {
        if (this.onceValue && alreadySeen(this.idValue, this.onceValue)) {
            return;
        }
        const config = {
            ...this.optionsValue,
            steps: resolveSteps(this.stepsValue, this.stepTargets),
        };
        this.dispatch('pre-connect', { detail: { config }, prefix: 'ux-driver' });
        this.instance = driver(config);
        this.dispatch('connect', { detail: { driver: this.instance }, prefix: 'ux-driver' });
        this.instance.drive();
        markSeen(this.idValue, this.onceValue);
    }
    highlight() {
        const steps = resolveSteps(this.stepsValue, this.stepTargets);
        const step = steps[0];
        if (!step) {
            return;
        }
        const config = { ...this.optionsValue };
        this.dispatch('pre-connect', { detail: { config, step }, prefix: 'ux-driver' });
        this.instance = driver(config);
        this.dispatch('connect', { detail: { driver: this.instance }, prefix: 'ux-driver' });
        this.instance.highlight({
            element: step.element,
            popover: step.popover,
        });
    }
    disconnect() {
        this.instance?.destroy();
        this.instance = null;
    }
}
default_1.targets = ['step'];
default_1.values = {
    id: String,
    steps: { type: Array, default: [] },
    options: { type: Object, default: {} },
    autostart: { type: Boolean, default: false },
    once: { type: Boolean, default: false },
};
export default default_1;
//# sourceMappingURL=controller.js.map