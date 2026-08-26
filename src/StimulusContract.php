<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver;

/**
 * The PHP↔JS seam, declared once.
 *
 * Every name below also exists in assets/src: the `static values` and `static targets` blocks
 * and the action methods of controller.ts / hints-controller.ts, and the `data-step-*` /
 * `data-hint-*` attributes tour-utils.ts / hint-utils.ts read back through `dataset`. The Twig
 * templates and UXDriverExtension render from here rather than from their own copy, and
 * tests/StimulusContractTest.php parses those sources: renaming one side only turns it red.
 */
final class StimulusContract
{
    /** Controller identifiers, as Stimulus dasherizes them for the DOM. */
    public const TOUR = 'pentiminax--ux-driver--tour';

    public const HINTS = 'pentiminax--ux-driver--hints';

    /**
     * The same controllers, as StimulusHelper takes them: package name plus the
     * `symfony.controllers` key of assets/package.json.
     *
     * @var array<string, string>
     */
    public const CONTROLLERS = [
        self::TOUR  => '@pentiminax/ux-driver/tour',
        self::HINTS => '@pentiminax/ux-driver/hints',
    ];

    /**
     * `static values` of each controller, in declaration order.
     *
     * @var array<string, list<string>>
     */
    public const VALUES = [
        self::TOUR  => ['id', 'steps', 'options', 'autostart', 'once'],
        self::HINTS => ['id', 'hints', 'options', 'autostart'],
    ];

    /**
     * `static targets` of each controller. The target name doubles as the `data-<target>-*`
     * prefix of the attributes that target carries.
     *
     * @var array<string, string>
     */
    public const TARGETS = [
        self::TOUR  => 'step',
        self::HINTS => 'hint',
    ];

    /**
     * Public controller methods, minus the Stimulus lifecycle: everything `ux_tour_action()`
     * and `ux_hints_action()` may wire to.
     *
     * @var array<string, list<string>>
     */
    public const ACTIONS = [
        self::TOUR  => ['start', 'highlight', 'next', 'previous', 'moveTo', 'refresh', 'destroy'],
        self::HINTS => ['show', 'hide', 'open', 'close', 'dismiss', 'restore', 'restoreAll', 'refresh'],
    ];

    /**
     * The `data-step-*` / `data-hint-*` attributes each target carries, in render order.
     * `config` holds every remaining driver.js option as JSON; the others travel on their own
     * because they are escaped separately or read before the payload is parsed.
     *
     * @var array<string, list<string>>
     */
    public const ATTRIBUTES = [
        self::TOUR  => ['order', 'side', 'align', 'title', 'description', 'centered', 'config'],
        self::HINTS => ['side', 'align', 'id', 'title', 'description', 'config'],
    ];

    /**
     * The controller declaration and its values, keyed by value name.
     *
     * @param array<string, string> $values value name => rendered attribute value
     *
     * @return array<string, string>
     *
     * @throws \InvalidArgumentException when a value is not declared by the controller
     */
    public static function controllerAttributes(string $identifier, array $values): array
    {
        self::assertDeclared($identifier, array_keys($values), self::VALUES[$identifier], 'value');

        $attributes = ['data-controller' => $identifier];

        foreach (self::VALUES[$identifier] as $name) {
            if (isset($values[$name])) {
                $attributes[\sprintf('data-%s-%s-value', $identifier, $name)] = $values[$name];
            }
        }

        return $attributes;
    }

    /**
     * The target declaration and its data attributes. A null value renders no attribute at
     * all: ComponentAttributes rejects null, and driver.js falls back to its own default.
     *
     * @param array<string, string|null> $attributes attribute name => rendered value
     *
     * @return array<string, string>
     *
     * @throws \InvalidArgumentException when an attribute is not read by the controller
     */
    public static function targetAttributes(string $identifier, array $attributes): array
    {
        self::assertDeclared($identifier, array_keys($attributes), self::ATTRIBUTES[$identifier], 'attribute');

        $target   = self::TARGETS[$identifier];
        $rendered = [\sprintf('data-%s-target', $identifier) => $target];

        foreach (self::ATTRIBUTES[$identifier] as $name) {
            if (null !== ($attributes[$name] ?? null)) {
                $rendered[\sprintf('data-%s-%s', $target, $name)] = $attributes[$name];
            }
        }

        return $rendered;
    }

    /**
     * @param list<string> $names
     * @param list<string> $declared
     *
     * @throws \InvalidArgumentException
     */
    private static function assertDeclared(string $identifier, array $names, array $declared, string $kind): void
    {
        $unknown = array_diff($names, $declared);

        if ([] !== $unknown) {
            throw new \InvalidArgumentException(\sprintf('Unknown %s %s for controller "%s". Declared %ss are: %s.', $kind, implode(', ', array_map(static fn (string $name) => '"'.$name.'"', $unknown)), $identifier, $kind, implode(', ', $declared)));
        }
    }
}
