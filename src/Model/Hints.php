<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Html\DriverOptions;
use Twig\Markup;

/**
 * A group of driver.js hints: persistent beacons the user can open at will, as opposed to the
 * linear walkthrough a Tour drives.
 *
 * @phpstan-import-type HintOptions from Hint
 */
final class Hints
{
    use OverlayOptions;

    /**
     * The group-wide driver.js options, in payload order, mapped to how their value is
     * normalized. The setters and the declarative mode both write through this table.
     */
    private const OPTIONS = [
        'beacon'         => Hint::BEACON_OPTIONS,
        'buttonText'     => null,
        'popoverClass'   => null,
        'popoverOffset'  => null,
        'overlay'        => null,
        'overlayColor'   => null,
        'overlayOpacity' => null,
    ];

    /** @var Hint[] */
    private array $hints = [];

    public function __construct(public readonly string $id)
    {
        $this->options = [];
    }

    /**
     * @param string      $element a CSS selector
     * @param string|null $id      a stable identifier for `open()`, `dismiss()` and `restore()`
     * @param HintOptions $options
     *
     * @throws \InvalidArgumentException when an option is not a serializable driver.js hint option
     * @throws \ValueError               when a side or alignment value is unknown
     */
    public function addHint(
        string $element,
        ?string $id = null,
        string|Markup|null $title = null,
        string|Markup|null $description = null,
        Side|string $side = Side::Bottom,
        Align|string $align = Align::Start,
        array $options = [],
    ): self {
        $this->hints[] = new Hint($element, $id, $title, $description, $side, $align, $options);

        return $this;
    }

    /**
     * Applies a map of driver.js options at once — what the Twig component hands over, so the
     * declarative mode validates and normalizes exactly like the builder.
     *
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException when an option is not a group-wide driver.js option
     * @throws \ValueError               when a beacon side or alignment is unknown
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, Options::normalizeAll($options, self::OPTIONS, 'hints'));

        return $this;
    }

    /**
     * The default beacon for every hint of the group.
     *
     * @throws \ValueError when the side or the alignment is unknown
     */
    public function beacon(
        Side|string|null $side = null,
        Align|string|null $align = null,
        ?bool $animate = null,
        ?string $className = null,
    ): self {
        return $this->options(['beacon' => array_filter([
            'side'      => $side,
            'align'     => $align,
            'animate'   => $animate,
            'className' => $className,
        ], static fn ($value) => null !== $value)]);
    }

    /**
     * driver.js writes the button label with innerHTML, hence the same escaping as the popover
     * content, applied by DriverOptions when the config is serialized.
     */
    public function buttonText(string|Markup $text): self
    {
        $this->options['buttonText'] = $text;

        return $this;
    }

    public function overlay(bool $value = true): self
    {
        $this->options['overlay'] = $value;

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getHints(): array
    {
        return array_values(array_map(static fn (Hint $hint) => $hint->toArray(), $this->hints));
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return DriverOptions::escape($this->options);
    }
}
