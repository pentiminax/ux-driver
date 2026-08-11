export function storageKey(id) {
    return `ux-driver:seen:${id}`;
}
export function alreadySeen(id, once) {
    if (!once) {
        return false;
    }
    try {
        return window.localStorage.getItem(storageKey(id)) === '1';
    }
    catch {
        return false;
    }
}
export function markSeen(id, once) {
    if (!once) {
        return;
    }
    try {
        window.localStorage.setItem(storageKey(id), '1');
    }
    catch {
    }
}
export function forgetSeen(id) {
    try {
        window.localStorage.removeItem(storageKey(id));
    }
    catch {
    }
}
export function resolveSteps(builderSteps, stepTargets) {
    if (builderSteps.length > 0) {
        return builderSteps;
    }
    return stepTargets
        .slice()
        .sort((left, right) => Number(left.dataset.stepOrder ?? 0) - Number(right.dataset.stepOrder ?? 0))
        .map((element) => ({
        element,
        popover: {
            title: element.dataset.stepTitle,
            description: element.dataset.stepDescription,
            side: element.dataset.stepSide,
            align: element.dataset.stepAlign,
        },
    }));
}
//# sourceMappingURL=tour-utils.js.map