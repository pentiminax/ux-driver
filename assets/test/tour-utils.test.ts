import {afterEach, describe, expect, it, vi} from 'vitest';
import {alreadySeen, forgetSeen, markSeen, resolveSteps, storageKey} from '../src/tour-utils.js';

describe('storageKey', () => {
    it('builds a namespaced key from the tour id', () => {
        expect(storageKey('onboarding')).toBe('ux-driver:seen:onboarding');
    });
});

describe('alreadySeen / markSeen / forgetSeen', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        localStorage.clear();
    });

    it('returns false when once is disabled', () => {
        expect(alreadySeen('onboarding', false)).toBe(false);
    });

    it('persists a seen flag when once is enabled', () => {
        markSeen('onboarding', true);

        expect(alreadySeen('onboarding', true)).toBe(true);
    });

    it('never touches storage when once is disabled', () => {
        const getItem = vi.spyOn(Storage.prototype, 'getItem');
        const setItem = vi.spyOn(Storage.prototype, 'setItem');

        alreadySeen('onboarding', false);
        markSeen('onboarding', false);

        expect(getItem).not.toHaveBeenCalled();
        expect(setItem).not.toHaveBeenCalled();
    });

    it('forgets a persisted flag so the tour can play again', () => {
        markSeen('onboarding', true);

        forgetSeen('onboarding');

        expect(alreadySeen('onboarding', true)).toBe(false);
    });

    it.each([
        ['getItem', () => expect(alreadySeen('onboarding', true)).toBe(false)],
        ['setItem', () => markSeen('onboarding', true)],
        ['removeItem', () => forgetSeen('onboarding')],
    ])('survives a throwing %s', (method, act) => {
        vi.spyOn(Storage.prototype, method as 'getItem' | 'setItem' | 'removeItem').mockImplementation(
            () => {
                throw new Error('SecurityError');
            },
        );

        expect(act).not.toThrow();
    });

    it('survives a window.localStorage getter that throws', () => {
        const descriptor = Object.getOwnPropertyDescriptor(window, 'localStorage');

        Object.defineProperty(window, 'localStorage', {
            configurable: true,
            get() {
                throw new Error('SecurityError');
            },
        });

        try {
            expect(alreadySeen('onboarding', true)).toBe(false);
            expect(() => markSeen('onboarding', true)).not.toThrow();
            expect(() => forgetSeen('onboarding')).not.toThrow();
        } finally {
            Object.defineProperty(window, 'localStorage', descriptor!);
        }
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

    it('merges the nested step config declared by PHP', () => {
        const target = document.createElement('div');
        target.dataset.stepTitle = 'Panier';
        target.dataset.stepSide = 'top';
        target.dataset.stepAlign = 'center';
        target.dataset.stepConfig = JSON.stringify({
            popover: {popoverClass: 'promo', showButtons: ['next'], nextBtnText: 'Suivant'},
            advanceOnClick: true,
            waitForElement: 2000,
            skipMissingElement: true,
            data: {tracking: 'cart'},
        });

        expect(resolveSteps([], [target])).toEqual([
            {
                element: target,
                popover: {
                    popoverClass: 'promo',
                    showButtons: ['next'],
                    nextBtnText: 'Suivant',
                    title: 'Panier',
                    description: undefined,
                    side: 'top',
                    align: 'center',
                },
                advanceOnClick: true,
                waitForElement: 2000,
                skipMissingElement: true,
                data: {tracking: 'cart'},
            },
        ]);
    });

    it('omits the element of a centered step so driver.js centers the popover', () => {
        const target = document.createElement('template');
        target.dataset.stepCentered = 'true';
        target.dataset.stepTitle = 'Bienvenue';

        const [step] = resolveSteps([], [target]);

        expect(step).not.toHaveProperty('element');
        expect(step?.popover?.title).toBe('Bienvenue');
    });

    it('falls back to the attribute options when the config payload is malformed', () => {
        const target = document.createElement('div');
        target.dataset.stepTitle = 'En-tête';
        target.dataset.stepConfig = '{not json';

        expect(resolveSteps([], [target])[0]).toEqual({
            element: target,
            popover: {
                title: 'En-tête',
                description: undefined,
                side: undefined,
                align: undefined,
            },
        });
    });
});
