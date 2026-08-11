export function resolveHints(builderHints, hintTargets) {
    if (builderHints.length > 0) {
        return builderHints;
    }
    return hintTargets.map(declaredHint);
}
function declaredHint(element) {
    const config = parseConfig(element.dataset.hintConfig);
    return {
        ...config,
        element,
        id: element.dataset.hintId,
        popover: {
            ...config.popover,
            title: element.dataset.hintTitle,
            description: element.dataset.hintDescription,
            side: element.dataset.hintSide,
            align: element.dataset.hintAlign,
        },
    };
}
function parseConfig(json) {
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
//# sourceMappingURL=hint-utils.js.map