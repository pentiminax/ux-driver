import {describe, expect, it} from 'vitest';
import {resolveHints} from '../src/hint-utils.js';
import type {DriverHint} from 'driver.js/hints';

function target(dataset: Record<string, string>): HTMLElement {
    const element = document.createElement('div');

    Object.assign(element.dataset, dataset);

    return element;
}

describe('resolveHints', () => {
    it('prefers the hints declared by the builder over the DOM targets', () => {
        const builderHints: DriverHint[] = [{element: '.export', popover: {title: 'Exporter'}}];

        expect(resolveHints(builderHints, [target({hintTitle: 'Ignoré'})])).toBe(builderHints);
    });

    it('reads the declarative hint targets when the builder declared nothing', () => {
        const element = target({
            hintId: 'export',
            hintTitle: 'Exporter',
            hintDescription: 'Téléchargez vos données',
            hintSide: 'top',
            hintAlign: 'center',
        });

        expect(resolveHints([], [element])).toEqual([
            {
                element,
                id: 'export',
                popover: {
                    title: 'Exporter',
                    description: 'Téléchargez vos données',
                    side: 'top',
                    align: 'center',
                },
            },
        ]);
    });

    it('merges the nested hint config declared by PHP', () => {
        const element = target({
            hintTitle: 'Exporter',
            hintSide: 'bottom',
            hintAlign: 'start',
            hintConfig: JSON.stringify({
                beacon: {side: 'top', animate: false},
                popover: {popoverClass: 'help-popover', showButton: true, buttonText: 'Compris'},
                data: {tracking: 'export'},
            }),
        });

        expect(resolveHints([], [element])).toEqual([
            {
                element,
                id: undefined,
                beacon: {side: 'top', animate: false},
                popover: {
                    popoverClass: 'help-popover',
                    showButton: true,
                    buttonText: 'Compris',
                    title: 'Exporter',
                    description: undefined,
                    side: 'bottom',
                    align: 'start',
                },
                data: {tracking: 'export'},
            },
        ]);
    });

    it('falls back to the attribute options when the config payload is malformed', () => {
        const element = target({hintTitle: 'Exporter', hintConfig: '{not json'});

        expect(resolveHints([], [element])).toEqual([
            {
                element,
                id: undefined,
                popover: {
                    title: 'Exporter',
                    description: undefined,
                    side: undefined,
                    align: undefined,
                },
            },
        ]);
    });

    it('keeps the declaration order: beacons are not numbered like tour steps', () => {
        const elements = [target({hintTitle: 'B'}), target({hintTitle: 'A'})];

        expect(resolveHints([], elements).map((hint) => hint.popover?.title)).toEqual(['B', 'A']);
    });

    it('resolves nothing when neither mode declares a hint', () => {
        expect(resolveHints([], [])).toEqual([]);
    });
});
