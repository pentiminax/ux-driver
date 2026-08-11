import type {DriveStep} from 'driver.js';

export function storageKey(id: string): string {
    return `ux-driver:seen:${id}`;
}

export function alreadySeen(id: string, once: boolean): boolean {
    return once && localStorage.getItem(storageKey(id)) === '1';
}

export function markSeen(id: string, once: boolean): void {
    if (once) {
        localStorage.setItem(storageKey(id), '1');
    }
}

export function resolveSteps(builderSteps: DriveStep[], stepTargets: HTMLElement[]): DriveStep[] {
    if (builderSteps.length > 0) {
        return builderSteps;
    }

    return stepTargets
        .slice()
        .sort(
            (left, right) =>
                Number(left.dataset.stepOrder ?? 0) - Number(right.dataset.stepOrder ?? 0),
        )
        .map((element) => ({
            element,
            popover: {
                title: element.dataset.stepTitle,
                description: element.dataset.stepDescription,
                side: element.dataset.stepSide as DriveStep['popover'] extends infer P
                    ? P extends {side?: infer S}
                        ? S
                        : never
                    : never,
                align: element.dataset.stepAlign as DriveStep['popover'] extends infer P
                    ? P extends {align?: infer A}
                        ? A
                        : never
                    : never,
            },
        }));
}
