import { Controller } from '@hotwired/stimulus';
import { hints } from 'driver.js/hints';
import { resolveHints } from './hint-utils.js';
class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.instance = null;
    }
    connect() {
        if (this.autostartValue) {
            this.show();
        }
    }
    show() {
        const declared = resolveHints(this.hintsValue, this.hintTargets);
        if (declared.length === 0) {
            this.dispatch('empty', { detail: { id: this.idValue }, prefix: 'ux-driver' });
            return;
        }
        const config = { ...this.optionsValue, ...this.hooks(), hints: declared };
        this.dispatch('hints-pre-connect', { detail: { config }, prefix: 'ux-driver' });
        this.teardown();
        this.instance = hints(config);
        this.dispatch('hints-connect', { detail: { hints: this.instance }, prefix: 'ux-driver' });
        this.instance.show();
    }
    hide() {
        this.teardown();
    }
    open(event) {
        const id = this.hintIdParam(event);
        if (id !== undefined) {
            this.instance?.open(id);
        }
    }
    close() {
        this.instance?.close();
    }
    dismiss(event) {
        const id = this.hintIdParam(event);
        if (id !== undefined) {
            this.instance?.dismiss(id);
        }
    }
    restore(event) {
        const id = this.hintIdParam(event);
        if (id !== undefined) {
            this.instance?.restore(id);
        }
    }
    restoreAll() {
        this.instance?.getHints().forEach((hint, index) => {
            this.instance?.restore(hint.id ?? index);
        });
    }
    refresh() {
        this.instance?.refresh();
    }
    disconnect() {
        this.teardown();
    }
    teardown() {
        this.instance?.hide();
        this.instance = null;
    }
    hintIdParam(event) {
        const id = event.params?.hintId;
        return id === undefined || id === '' ? undefined : id;
    }
    hooks() {
        return {
            onOpen: this.observe('hint-open'),
            onDismiss: this.observe('hint-dismiss'),
            onButtonClick: this.observe('hint-button-click'),
        };
    }
    observe(name) {
        return (element, hint) => {
            this.dispatch(name, {
                detail: {
                    groupId: this.idValue,
                    hintId: hint.id,
                    hint,
                    element,
                    data: hint.data,
                    hints: this.instance,
                },
                prefix: 'ux-driver',
            });
        };
    }
}
default_1.targets = ['hint'];
default_1.values = {
    id: String,
    hints: { type: Array, default: [] },
    options: { type: Object, default: {} },
    autostart: { type: Boolean, default: true },
};
export default default_1;
//# sourceMappingURL=hints-controller.js.map