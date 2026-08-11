<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Pentiminax\UX\Driver\Model\Hints as HintsModel;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Twig\Markup;

#[AsTwigComponent('Driver:Hints')]
final class Hints
{
    public string $id;

    /** Paint the beacons as soon as the controller connects. */
    public bool $autostart = true;

    /** @var array<string, mixed>|null */
    public ?array $beacon = null;

    public string|Markup|null $buttonText = null;

    public ?string $popoverClass = null;

    public ?int $popoverOffset = null;

    public ?bool $overlay = null;

    public ?string $overlayColor = null;

    public ?float $overlayOpacity = null;

    /**
     * Serialized through the very model the builder mode uses, so both modes validate and
     * shape their config identically.
     *
     * @return array<string, mixed>
     *
     * @throws \ValueError when a beacon side or alignment is unknown
     */
    public function options(): array
    {
        $hints = new HintsModel($this->id);

        if (null !== $this->beacon) {
            /** @var array{side?: string, align?: string, animate?: bool, className?: string} $beacon */
            $beacon = $this->beacon;

            $hints->beacon(
                side: $beacon['side']           ?? null,
                align: $beacon['align']         ?? null,
                animate: $beacon['animate']     ?? null,
                className: $beacon['className'] ?? null,
            );
        }

        if (null !== $this->buttonText) {
            $hints->buttonText($this->buttonText);
        }

        if (null !== $this->popoverClass) {
            $hints->popoverClass($this->popoverClass);
        }

        if (null !== $this->popoverOffset) {
            $hints->popoverOffset($this->popoverOffset);
        }

        if (null !== $this->overlay) {
            $hints->overlay($this->overlay);
        }

        if (null !== $this->overlayColor) {
            $hints->overlayColor($this->overlayColor);
        }

        if (null !== $this->overlayOpacity) {
            $hints->overlayOpacity($this->overlayOpacity);
        }

        return $hints->getOptions();
    }
}
