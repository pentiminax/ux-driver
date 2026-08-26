<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Html\PopoverContent;
use Pentiminax\UX\Driver\Model\Step as StepModel;
use Pentiminax\UX\Driver\StimulusContract;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Twig\Markup;

#[AsTwigComponent('Driver:Step')]
final class Step
{
    public int $order = 0;

    public string|Markup|null $title = null;

    public string|Markup|null $description = null;

    public string $side = 'bottom';

    public string $align = 'start';

    public string $tag = 'div';

    /**
     * A centered step has no target: driver.js places the popover in the middle of the screen.
     * The component then renders an inert `<template>` instead of a fake target element — the
     * tag still carries the data attributes, but nothing is added to the page layout.
     */
    public bool $centered = false;

    public ?string $popoverClass = null;

    /** @var list<Button|string>|null */
    public ?array $showButtons = null;

    /** @var list<Button|string>|null */
    public ?array $disableButtons = null;

    public ?bool $showProgress = null;

    public string|Markup|null $progressText = null;

    public string|Markup|null $nextBtnText = null;

    public string|Markup|null $prevBtnText = null;

    public string|Markup|null $doneBtnText = null;

    public ?bool $disableActiveInteraction = null;

    public ?bool $advanceOnClick = null;

    public ?bool $skipMissingElement = null;

    public ?int $waitForElement = null;

    /** @var array<string, mixed>|null */
    public ?array $data = null;

    public function tagName(): string
    {
        return $this->centered ? 'template' : $this->tag;
    }

    /**
     * The target declaration and the `data-step-*` attributes the tour controller reads back
     * through `dataset`, derived from StimulusContract so an attribute renamed on the JS side
     * cannot keep rendering here.
     *
     * @return array<string, string>
     *
     * @throws \ValueError when a side, an alignment or a button name is unknown
     */
    public function stimulusAttributes(): array
    {
        return StimulusContract::targetAttributes(StimulusContract::TOUR, [
            'order'       => (string) $this->order,
            'side'        => $this->sideValue(),
            'align'       => $this->alignValue(),
            'title'       => $this->titleHtml(),
            'description' => $this->descriptionHtml(),
            'centered'    => $this->centered ? 'true' : null,
            'config'      => $this->configJson(),
        ]);
    }

    /**
     * The template must render these, not the raw properties: the controller decodes the
     * attribute once through `dataset` before handing it to innerHTML.
     */
    public function titleHtml(): ?string
    {
        return PopoverContent::render($this->title);
    }

    public function descriptionHtml(): ?string
    {
        return PopoverContent::render($this->description);
    }

    /**
     * @throws \ValueError when the side is unknown
     */
    public function sideValue(): string
    {
        return $this->model()->getSide();
    }

    /**
     * @throws \ValueError when the alignment is unknown
     */
    public function alignValue(): string
    {
        return $this->model()->getAlign();
    }

    /**
     * The remaining driver.js step options, already nested the way the controller hands them
     * to driver.js. Serialized through the very model the builder mode uses, so both modes
     * validate and shape their payload identically.
     *
     * @throws \ValueError when a button name is unknown
     */
    public function configJson(): ?string
    {
        $extras = $this->model()->extras();

        return [] === $extras ? null : json_encode($extras, \JSON_THROW_ON_ERROR);
    }

    private function model(): StepModel
    {
        return new StepModel(
            side: $this->side,
            align: $this->align,
            options: $this->options(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return array_filter([
            'popoverClass'             => $this->popoverClass,
            'showButtons'              => $this->showButtons,
            'disableButtons'           => $this->disableButtons,
            'showProgress'             => $this->showProgress,
            'progressText'             => $this->progressText,
            'nextBtnText'              => $this->nextBtnText,
            'prevBtnText'              => $this->prevBtnText,
            'doneBtnText'              => $this->doneBtnText,
            'disableActiveInteraction' => $this->disableActiveInteraction,
            'advanceOnClick'           => $this->advanceOnClick,
            'skipMissingElement'       => $this->skipMissingElement,
            'waitForElement'           => $this->waitForElement,
            'data'                     => $this->data,
        ], static fn ($value) => null !== $value);
    }
}
