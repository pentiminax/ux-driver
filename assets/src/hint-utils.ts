import type {Alignment, DriverHint, Side} from 'driver.js/hints';
import {parseConfig, resolveDeclarative} from './config-utils.js';

export function resolveHints(builderHints: DriverHint[], hintTargets: HTMLElement[]): DriverHint[] {
    return resolveDeclarative(builderHints, hintTargets, declaredHint);
}

/**
 * PHP already nested everything driver.js expects into `data-hint-config`; only the target
 * element, its id and the escaped popover content travel as their own attributes.
 */
function declaredHint(element: HTMLElement): DriverHint {
    const config = parseConfig<DriverHint>(element.dataset.hintConfig);

    return {
        ...config,
        element,
        id: element.dataset.hintId,
        popover: {
            ...config.popover,
            title: element.dataset.hintTitle,
            description: element.dataset.hintDescription,
            side: element.dataset.hintSide as Side,
            align: element.dataset.hintAlign as Alignment,
        },
    };
}
