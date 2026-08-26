import { parseConfig, resolveDeclarative } from './config-utils.js';
export function resolveHints(builderHints, hintTargets) {
    return resolveDeclarative(builderHints, hintTargets, declaredHint);
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
//# sourceMappingURL=hint-utils.js.map