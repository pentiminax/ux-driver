export function storageKey(id) {
    return `ux-driver:seen:${id}`;
}
export function alreadySeen(id, once) {
    return once && localStorage.getItem(storageKey(id)) === '1';
}
export function markSeen(id, once) {
    if (once) {
        localStorage.setItem(storageKey(id), '1');
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