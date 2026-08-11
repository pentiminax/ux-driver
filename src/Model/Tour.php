<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\OverlayClickBehavior;
use Pentiminax\UX\Driver\Html\PopoverContent;
use Twig\Markup;

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
        string|Markup $title,
        string|Markup|null $description = null,
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

    public function duration(int $milliseconds): self
    {
        $this->options['duration'] = $milliseconds;

        return $this;
    }

    public function allowScroll(bool $value = true): self
    {
        $this->options['allowScroll'] = $value;

        return $this;
    }

    /**
     * @throws \ValueError when the behaviour is neither `close` nor `nextStep`
     */
    public function overlayClickBehavior(OverlayClickBehavior|string $behavior): self
    {
        $this->options['overlayClickBehavior'] = ($behavior instanceof OverlayClickBehavior
            ? $behavior
            : OverlayClickBehavior::from($behavior))->value;

        return $this;
    }

    public function stageRadius(int $radius): self
    {
        $this->options['stageRadius'] = $radius;

        return $this;
    }

    public function allowKeyboardControl(bool $value = true): self
    {
        $this->options['allowKeyboardControl'] = $value;

        return $this;
    }

    public function disableActiveInteraction(bool $value = true): self
    {
        $this->options['disableActiveInteraction'] = $value;

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

    /**
     * @throws \ValueError when a button is not one of `next`, `previous`, `close`
     */
    public function showButtons(Button|string ...$buttons): self
    {
        $this->options['showButtons'] = Button::normalizeAll($buttons);

        return $this;
    }

    /**
     * @throws \ValueError when a button is not one of `next`, `previous`, `close`
     */
    public function disableButtons(Button|string ...$buttons): self
    {
        $this->options['disableButtons'] = Button::normalizeAll($buttons);

        return $this;
    }

    /**
     * driver.js substitutes `{{current}}` and `{{total}}` in the template, then writes it with
     * innerHTML — hence the same escaping as the popover content.
     */
    public function progressText(string|Markup $template): self
    {
        $this->options['progressText'] = PopoverContent::render($template);

        return $this;
    }

    public function nextBtnText(string|Markup $text): self
    {
        $this->options['nextBtnText'] = PopoverContent::render($text);

        return $this;
    }

    public function prevBtnText(string|Markup $text): self
    {
        $this->options['prevBtnText'] = PopoverContent::render($text);

        return $this;
    }

    public function doneBtnText(string|Markup $text): self
    {
        $this->options['doneBtnText'] = PopoverContent::render($text);

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
