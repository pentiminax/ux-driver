<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\OverlayClickBehavior;
use Pentiminax\UX\Driver\Model\Tour as TourModel;
use Pentiminax\UX\Driver\StimulusContract;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Twig\Markup;

#[AsTwigComponent('Driver:Tour')]
final class Tour
{
    public string $id;

    public bool $autostart = false;

    public bool $once = false;

    public ?bool $showProgress = null;

    public ?bool $animate = null;

    public ?bool $smoothScroll = null;

    public ?bool $allowClose = null;

    public ?string $overlayColor = null;

    public ?float $overlayOpacity = null;

    public ?int $stagePadding = null;

    public ?int $duration = null;

    public ?bool $allowScroll = null;

    public OverlayClickBehavior|string|null $overlayClickBehavior = null;

    public ?int $stageRadius = null;

    public ?bool $allowKeyboardControl = null;

    public ?bool $disableActiveInteraction = null;

    public ?string $popoverClass = null;

    public ?int $popoverOffset = null;

    /** @var list<Button|string>|null */
    public ?array $showButtons = null;

    /** @var list<Button|string>|null */
    public ?array $disableButtons = null;

    public string|Markup|null $progressText = null;

    public string|Markup|null $nextBtnText = null;

    public string|Markup|null $prevBtnText = null;

    public string|Markup|null $doneBtnText = null;

    /**
     * The attributes the tour controller reads, derived from StimulusContract so a value
     * renamed on the JS side cannot keep rendering here.
     *
     * @return array<string, string>
     *
     * @throws \JsonException when an option cannot be serialized
     * @throws \ValueError    when a button name or the overlay click behaviour is unknown
     */
    public function stimulusAttributes(): array
    {
        return StimulusContract::controllerAttributes(StimulusContract::TOUR, [
            'id'        => $this->id,
            'options'   => json_encode($this->options(), \JSON_THROW_ON_ERROR),
            'autostart' => $this->autostart ? 'true' : 'false',
            'once'      => $this->once ? 'true' : 'false',
        ]);
    }

    /**
     * The global driver.js config, serialized through the very model the builder mode uses:
     * one place validates the options and one place pins the defaults, so a bare component and
     * a bare builder send the same payload.
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when an option is not a global driver.js option
     * @throws \ValueError               when a button name or the overlay click behaviour is unknown
     */
    public function options(): array
    {
        return (new TourModel($this->id))->options(array_filter([
            'showProgress'             => $this->showProgress,
            'animate'                  => $this->animate,
            'smoothScroll'             => $this->smoothScroll,
            'allowClose'               => $this->allowClose,
            'overlayColor'             => $this->overlayColor,
            'overlayOpacity'           => $this->overlayOpacity,
            'stagePadding'             => $this->stagePadding,
            'duration'                 => $this->duration,
            'allowScroll'              => $this->allowScroll,
            'overlayClickBehavior'     => $this->overlayClickBehavior,
            'stageRadius'              => $this->stageRadius,
            'allowKeyboardControl'     => $this->allowKeyboardControl,
            'disableActiveInteraction' => $this->disableActiveInteraction,
            'popoverClass'             => $this->popoverClass,
            'popoverOffset'            => $this->popoverOffset,
            'showButtons'              => $this->showButtons,
            'disableButtons'           => $this->disableButtons,
            'progressText'             => $this->progressText,
            'nextBtnText'              => $this->nextBtnText,
            'prevBtnText'              => $this->prevBtnText,
            'doneBtnText'              => $this->doneBtnText,
        ], static fn ($value) => null !== $value))->getOptions();
    }
}
