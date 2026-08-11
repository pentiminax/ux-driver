import {afterEach, describe, expect, it} from 'vitest';
import {alreadySeen, markSeen, resolveSteps, storageKey} from '../src/tour-utils.js';

describe('storageKey', () => {
    it('builds a namespaced key from the tour id', () => {
        expect(storageKey('onboarding')).toBe('ux-driver:seen:onboarding');
    });
});

describe('alreadySeen / markSeen', () => {
    afterEach(() => {
        localStorage.clear();
    });

    it('returns false when once is disabled', () => {
        expect(alreadySeen('onboarding', false)).toBe(false);
    });

    it('persists a seen flag when once is enabled', () => {
        markSeen('onboarding', true);

        expect(alreadySeen('onboarding', true)).toBe(true);
    });
});

describe('resolveSteps', () => {
    it('prefers builder steps over declarative targets', () => {
        const builderSteps = [{element: '.header', popover: {title: 'Header'}}];
        const target = document.createElement('div');
        target.dataset.stepTitle = 'Sidebar';

        expect(resolveSteps(builderSteps, [target])).toEqual(builderSteps);
    });

    it('sorts declarative targets by order and maps popover data', () => {
        const second = document.createElement('aside');
        second.dataset.stepOrder = '2';
        second.dataset.stepTitle = 'Navigation';
        second.dataset.stepDescription = 'Menu principal';
        second.dataset.stepSide = 'right';
        second.dataset.stepAlign = 'start';

        const first = document.createElement('header');
        first.dataset.stepOrder = '1';
        first.dataset.stepTitle = 'En-tête';

        const steps = resolveSteps([], [second, first]);

        expect(steps).toEqual([
            {
                element: first,
                popover: {
                    title: 'En-tête',
                    description: undefined,
                    side: undefined,
                    align: undefined,
                },
            },
            {
                element: second,
                popover: {
                    title: 'Navigation',
                    description: 'Menu principal',
                    side: 'right',
                    align: 'start',
                },
            },
        ]);
    });
});
