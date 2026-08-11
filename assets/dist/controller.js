import { Controller } from '@hotwired/stimulus';
import { driver, } from 'driver.js';
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
    start(event) {
        if (this.onceValue && alreadySeen(this.idValue, this.onceValue)) {
            return;
        }
        const steps = resolveSteps(this.stepsValue, this.stepTargets);
        if (steps.length === 0) {
            this.dispatch('empty', { detail: { id: this.idValue }, prefix: 'ux-driver' });
            return;
        }
        const config = { ...this.optionsValue, ...this.hooks(), steps };
        this.dispatch('pre-connect', { detail: { config }, prefix: 'ux-driver' });
        this.teardown();
        this.instance = driver(config);
        this.dispatch('connect', { detail: { driver: this.instance }, prefix: 'ux-driver' });
        this.instance.drive(this.indexParam(event));
    }
    highlight() {
        const steps = resolveSteps(this.stepsValue, this.stepTargets);
        const step = steps[0];
        if (!step) {
            this.dispatch('empty', { detail: { id: this.idValue }, prefix: 'ux-driver' });
            return;
        }
        const config = { ...this.optionsValue, ...this.hooks() };
        this.dispatch('pre-connect', { detail: { config, step }, prefix: 'ux-driver' });
        this.teardown();
        this.instance = driver(config);
        this.dispatch('connect', { detail: { driver: this.instance }, prefix: 'ux-driver' });
        this.instance.highlight(step);
    }
    next() {
        this.instance?.moveNext();
    }
    previous() {
        this.instance?.movePrevious();
    }
    moveTo(event) {
        const index = this.indexParam(event);
        if (index !== undefined) {
            this.instance?.moveTo(index);
        }
    }
    refresh() {
        this.instance?.refresh();
    }
    destroy() {
        this.teardown();
    }
    disconnect() {
        this.teardown();
    }
    teardown() {
        this.instance?.destroy();
        this.instance = null;
    }
    indexParam(event) {
        const index = Number(event?.params?.index);
        return Number.isInteger(index) ? index : undefined;
    }
    hooks() {
        return {
            onHighlightStarted: this.observe('highlight-started'),
            onHighlighted: this.observe('highlighted'),
            onDeselected: this.observe('deselected'),
            onDestroyed: this.observe('destroyed'),
            onNextClick: this.intercept('next', (instance) => instance.moveNext()),
            onPrevClick: this.intercept('previous', (instance) => instance.movePrevious()),
            onCloseClick: this.intercept('close', (instance) => instance.destroy()),
            onDoneClick: this.intercept('done', (instance) => {
                markSeen(this.idValue, this.onceValue);
                instance.destroy();
            }),
            onDestroyStarted: this.intercept('destroy-started', (instance) => instance.destroy()),
        };
    }
    observe(name) {
        return (element, step, opts) => {
            this.dispatch(name, { detail: this.hookDetail(element, step, opts), prefix: 'ux-driver' });
        };
    }
    intercept(name, action) {
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
    hookDetail(element, step, opts) {
        return {
            tourId: this.idValue,
            index: opts.index,
            step,
            element,
            driver: opts.driver,
        };
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