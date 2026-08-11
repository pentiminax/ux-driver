<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

final class Tour
{
    /** @var Step[] */
    private array $steps = [];

    /** @var array<string, mixed> */
    private array $options = ['showProgress' => true];

    private bool $once = false;

    public function __construct(public readonly string $id)
    {
    }

    public function addStep(
        string $element,
        string $title,
        ?string $description = null,
        string $side = 'bottom',
        string $align = 'start',
    ): self {
        $this->steps[] = new Step($element, $title, $description, $side, $align);

        return $this;
    }

    public function showProgress(bool $value = true): self
    {
        $this->options['showProgress'] = $value;

        return $this;
    }

    public function animate(bool $value = true): self
    {
        $this->options['animate'] = $value;

        return $this;
    }

    public function smoothScroll(bool $value = true): self
    {
        $this->options['smoothScroll'] = $value;

        return $this;
    }

    public function allowClose(bool $value = true): self
    {
        $this->options['allowClose'] = $value;

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

    public function stagePadding(int $padding): self
    {
        $this->options['stagePadding'] = $padding;

        return $this;
    }

    public function once(bool $value = true): self
    {
        $this->once = $value;

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSteps(): array
    {
        return array_values(array_map(static fn (Step $step) => $step->toArray(), $this->steps));
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }
}
