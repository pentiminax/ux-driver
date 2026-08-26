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
        $this->options['beacon'] = Options::normalizeAll(array_filter([
            'side'      => $side,
            'align'     => $align,
            'animate'   => $animate,
            'className' => $className,
        ], static fn ($value) => null !== $value), Hint::BEACON_OPTIONS, 'beacon');

        return $this;
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
