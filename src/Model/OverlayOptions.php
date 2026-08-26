<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

/**
 * The driver.js options a Tour and a Hints group configure identically: the popover chrome and
 * the overlay painted behind it. Both models keep their own `$options` array; only these
 * setters are shared.
 */
trait OverlayOptions
{
    /**
     * The driver.js config both models build, seeded with their own defaults.
     *
     * @var array<string, mixed>
     */
    private array $options;

    public function popoverClass(string $class): static
    {
        $this->options['popoverClass'] = $class;

        return $this;
    }

    public function popoverOffset(int $offset): static
    {
        $this->options['popoverOffset'] = $offset;

        return $this;
    }

    public function overlayColor(string $color): static
    {
        $this->options['overlayColor'] = $color;

        return $this;
    }

    public function overlayOpacity(float $opacity): static
    {
        $this->options['overlayOpacity'] = $opacity;

        return $this;
    }
}
