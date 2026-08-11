<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Driver:Tour')]
final class Tour
{
    public string $id;

    public bool $autostart = false;

    public bool $once = false;

    public bool $showProgress = true;

    public bool $animate = true;

    public bool $smoothScroll = false;

    public bool $allowClose = true;

    public ?string $overlayColor = null;

    public ?float $overlayOpacity = null;

    public ?int $stagePadding = null;

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return array_filter([
            'showProgress'   => $this->showProgress,
            'animate'        => $this->animate,
            'smoothScroll'   => $this->smoothScroll,
            'allowClose'     => $this->allowClose,
            'overlayColor'   => $this->overlayColor,
            'overlayOpacity' => $this->overlayOpacity,
            'stagePadding'   => $this->stagePadding,
        ], static fn ($value) => null !== $value);
    }
}
