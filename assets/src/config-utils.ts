/**
 * What the tour and the hints utils do identically with their declarative targets: pick the
 * source of truth between the builder payload and the DOM, and parse back the nested config
 * PHP serialized into a single `data-*-config` attribute.
 *
 * The `data-step-*` / `data-hint-*` reads stay in their own module: they are written there as
 * literal `dataset.<name>` accesses, which is what tests/StimulusContractTest.php parses to
 * keep the PHP and JS sides of the attribute contract in sync.
 */

/**
 * A malformed payload would otherwise take the whole tour or hint group down; the item still
 * plays with the options that do travel as attributes.
 */
export function parseConfig<T>(json: string | undefined): Partial<T> {
    if (!json) {
        return {};
    }

    try {
        return JSON.parse(json) as Partial<T>;
    } catch {
        return {};
    }
}

/**
 * Builder items win: a tour or a hint group declared in PHP already carries everything, so the
 * DOM targets of the declarative mode are only read when there is nothing to drive.
 */
export function resolveDeclarative<T>(
    builderItems: T[],
    targets: HTMLElement[],
    declared: (element: HTMLElement) => T,
): T[] {
    if (builderItems.length > 0) {
        return builderItems;
    }

    return targets.map(declared);
}
