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
    /** @var Hint[] */
    private array $hints = [];

    /** @var array<string, mixed> */
    private array $options = [];

    public function __construct(public readonly string $id)
    {
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
        $this->options['beacon'] = array_filter([
            'side'      => null === $side ? null : Side::normalize($side),
            'align'     => null === $align ? null : Align::normalize($align),
            'animate'   => $animate,
            'className' => $className,
        ], static fn ($value) => null !== $value);

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

    public function popoverClass(string $class): self
    {
        $this->options['popoverClass'] = $class;

        return $this;
    }

    public function popoverOffset(int $offset): self
    {
        $this->options['popoverOffset'] = $offset;

        return $this;
    }

    public function overlay(bool $value = true): self
    {
        $this->options['overlay'] = $value;

        return $this;
    }

    public function overlayColor(string $color): self
    {
        $this->options['overlayColor'] = $color;

        return $this;
    }

    public function overlayOpacity(float $opacity): self
    {
        $this->options['overlayOpacity'] = $opacity;

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
