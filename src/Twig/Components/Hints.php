<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Pentiminax\UX\Driver\Model\Hints as HintsModel;
use Pentiminax\UX\Driver\StimulusContract;
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
     * The attributes the hints controller reads, derived from StimulusContract so a value
     * renamed on the JS side cannot keep rendering here.
     *
     * @return array<string, string>
     *
     * @throws \JsonException when an option cannot be serialized
     * @throws \ValueError    when a beacon side or alignment is unknown
     */
    public function stimulusAttributes(): array
    {
        return StimulusContract::controllerAttributes(StimulusContract::HINTS, [
            'id'        => $this->id,
            'options'   => json_encode($this->options(), \JSON_THROW_ON_ERROR),
            'autostart' => $this->autostart ? 'true' : 'false',
        ]);
    }

    /**
     * Serialized through the very model the builder mode uses, so both modes validate and
     * shape their config identically.
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when an option is not a group-wide driver.js option
     * @throws \ValueError               when a beacon side or alignment is unknown
     */
    public function options(): array
    {
        return (new HintsModel($this->id))->options(array_filter([
            'beacon'         => $this->beacon,
            'buttonText'     => $this->buttonText,
            'popoverClass'   => $this->popoverClass,
            'popoverOffset'  => $this->popoverOffset,
            'overlay'        => $this->overlay,
            'overlayColor'   => $this->overlayColor,
            'overlayOpacity' => $this->overlayOpacity,
        ], static fn ($value) => null !== $value))->getOptions();
    }
}
