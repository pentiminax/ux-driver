import type {Alignment, DriverHint, Side} from 'driver.js/hints';

/**
 * Builder hints win: a group declared in PHP already carries every hint, so the DOM targets
 * of the declarative mode are only read when there is nothing to drive.
 */
export function resolveHints(builderHints: DriverHint[], hintTargets: HTMLElement[]): DriverHint[] {
    if (builderHints.length > 0) {
        return builderHints;
    }

    return hintTargets.map(declaredHint);
}

/**
 * PHP already nested everything driver.js expects into `data-hint-config`; only the target
 * element, its id and the escaped popover content travel as their own attributes.
 */
function declaredHint(element: HTMLElement): DriverHint {
    const config = parseConfig(element.dataset.hintConfig);

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

function parseConfig(json: string | undefined): Partial<DriverHint> {
    if (!json) {
        return {};
    }

    try {
        return JSON.parse(json) as Partial<DriverHint>;
    } catch {
        // A malformed payload would otherwise take the whole group down; the hint still shows
        // with the options that do travel as attributes.
        return {};
    }
}
