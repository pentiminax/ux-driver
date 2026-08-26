export function parseConfig(json) {
    if (!json) {
        return {};
    }
    try {
        return JSON.parse(json);
    }
    catch {
        return {};
    }
}
export function resolveDeclarative(builderItems, targets, declared) {
    if (builderItems.length > 0) {
        return builderItems;
    }
    return targets.map(declared);
}
//# sourceMappingURL=config-utils.js.map