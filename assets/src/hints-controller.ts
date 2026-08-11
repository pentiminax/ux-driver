import {Controller, type ActionEvent} from '@hotwired/stimulus';
import {hints, type DriverHint, type HintHook, type Hints, type HintsConfig} from 'driver.js/hints';
import {resolveHints} from './hint-utils.js';

export default class extends Controller {
    static targets = ['hint'];

    static values = {
        id: String,
        hints: {type: Array, default: []},
        options: {type: Object, default: {}},
        autostart: {type: Boolean, default: true},
    };

    declare readonly hintTargets: HTMLElement[];
    declare readonly idValue: string;
    declare readonly hintsValue: DriverHint[];
    declare readonly optionsValue: HintsConfig;
    declare readonly autostartValue: boolean;

    private instance: Hints | null = null;

    connect(): void {
        if (this.autostartValue) {
            this.show();
        }
    }

    show(): void {
        const declared = resolveHints(this.hintsValue, this.hintTargets);

        if (declared.length === 0) {
            this.dispatch('empty', {detail: {id: this.idValue}, prefix: 'ux-driver'});

            return;
        }

        const config: HintsConfig = {...this.optionsValue, ...this.hooks(), hints: declared};

        this.dispatch('hints-pre-connect', {detail: {config}, prefix: 'ux-driver'});

        this.teardown();
        this.instance = hints(config);
        this.dispatch('hints-connect', {detail: {hints: this.instance}, prefix: 'ux-driver'});

        this.instance.show();
    }

    hide(): void {
        this.teardown();
    }

    open(event: ActionEvent): void {
        const id = this.hintIdParam(event);

        if (id !== undefined) {
            this.instance?.open(id);
        }
    }

    close(): void {
        this.instance?.close();
    }

    dismiss(event: ActionEvent): void {
        const id = this.hintIdParam(event);

        if (id !== undefined) {
            this.instance?.dismiss(id);
        }
    }

    restore(event: ActionEvent): void {
        const id = this.hintIdParam(event);

        if (id !== undefined) {
            this.instance?.restore(id);
        }
    }

    /**
     * driver.js has no bulk restore: `restore()` takes one id at a time, and a hint declared
     * without an id answers to its index instead.
     */
    restoreAll(): void {
        this.instance?.getHints().forEach((hint, index) => {
            this.instance?.restore(hint.id ?? index);
        });
    }

    refresh(): void {
        this.instance?.refresh();
    }

    disconnect(): void {
        this.teardown();
    }

    private teardown(): void {
        this.instance?.hide();
        this.instance = null;
    }

    private hintIdParam(event: ActionEvent): string | number | undefined {
        const id = event.params?.hintId as string | number | undefined;

        return id === undefined || id === '' ? undefined : id;
    }

    /**
     * driver.js only takes callbacks, which PHP and Twig cannot serialize. Every hook is
     * bridged to a Stimulus event so a group declared server-side stays observable.
     *
     * Unlike the tour hooks, none of these replaces a default behaviour: driver.js opens,
     * dismisses and closes on its own whether or not a callback is supplied.
     */
    private hooks(): HintsConfig {
        return {
            onOpen: this.observe('hint-open'),
            onDismiss: this.observe('hint-dismiss'),
            onButtonClick: this.observe('hint-button-click'),
        };
    }

    private observe(name: string): HintHook {
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
