import type {Alignment, DriveStep, Side} from 'driver.js';
import {parseConfig, resolveDeclarative} from './config-utils.js';

export function storageKey(id: string): string {
    return `ux-driver:seen:${id}`;
}

/**
 * Storage access can throw at any point: reading `window.localStorage` itself raises a
 * SecurityError in a sandboxed iframe or when cookies are blocked, and `setItem` raises
 * QuotaExceededError when the store is full or in some private-browsing modes.
 * Every access is therefore guarded; an unavailable store means the tour is treated as
 * never seen and simply replays.
 */
export function alreadySeen(id: string, once: boolean): boolean {
    if (!once) {
        return false;
    }

    try {
        return window.localStorage.getItem(storageKey(id)) === '1';
    } catch {
        return false;
    }
}

export function markSeen(id: string, once: boolean): void {
    if (!once) {
        return;
    }

    try {
        window.localStorage.setItem(storageKey(id), '1');
    } catch {
        // Nothing to persist to: the tour will play again on the next page load.
    }
}

export function forgetSeen(id: string): void {
    try {
        window.localStorage.removeItem(storageKey(id));
    } catch {
        // Nothing to clear: the flag was never persisted.
    }
}

export function resolveSteps(builderSteps: DriveStep[], stepTargets: HTMLElement[]): DriveStep[] {
    return resolveDeclarative(builderSteps, sortedByOrder(stepTargets), declaredStep);
}

function sortedByOrder(stepTargets: HTMLElement[]): HTMLElement[] {
    return stepTargets
        .slice()
        .sort(
            (left, right) =>
                Number(left.dataset.stepOrder ?? 0) - Number(right.dataset.stepOrder ?? 0),
        );
}

/**
 * PHP already nested everything driver.js expects into `data-step-config`; only the target
 * element and the escaped popover content travel as their own attributes.
 */
function declaredStep(element: HTMLElement): DriveStep {
    const config = parseConfig<DriveStep>(element.dataset.stepConfig);

    const step: DriveStep = {
        ...config,
        popover: {
            ...config.popover,
            title: element.dataset.stepTitle,
            description: element.dataset.stepDescription,
            side: element.dataset.stepSide as Side,
            align: element.dataset.stepAlign as Alignment,
        },
    };

    // A centered step declares no target: driver.js then places the popover in the middle of
    // the screen. Its `<template>` host must not become the highlighted element.
    if (element.dataset.stepCentered !== 'true') {
        step.element = element;
    }

    return step;
}
