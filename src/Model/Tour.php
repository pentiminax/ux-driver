<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\OverlayClickBehavior;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Html\DriverOptions;
use Twig\Markup;

/**
 * @phpstan-import-type StepOptions from Step
 */
final class Tour
{
    use OverlayOptions;

    /**
     * The one place a driver.js default is pinned: every authoring mode seeds its config here.
     *
     * @var array<string, mixed>
     */
    private const DEFAULTS = ['showProgress' => true];

    /** @var Step[] */
    private array $steps = [];

    private bool $once = false;

    public function __construct(public readonly string $id)
    {
        $this->options = self::DEFAULTS;
    }

    /**
     * @param string|null $element a CSS selector, or null for a step centered on the screen
     * @param StepOptions $options
     *
     * @throws \InvalidArgumentException when an option is not a serializable driver.js step option
     * @throws \ValueError               when a button, side or alignment value is unknown
     */
    public function addStep(
        ?string $element = null,
        string|Markup|null $title = null,
        string|Markup|null $description = null,
        Side|string $side = Side::Bottom,
        Align|string $align = Align::Start,
        array $options = [],
    ): self {
        $this->steps[] = new Step($element, $title, $description, $side, $align, $options);

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
     * innerHTML — hence the same escaping as the popover content, applied by DriverOptions
     * when the config is serialized.
     */
    public function progressText(string|Markup $template): self
    {
        $this->options['progressText'] = $template;

        return $this;
    }

    public function nextBtnText(string|Markup $text): self
    {
        $this->options['nextBtnText'] = $text;

        return $this;
    }

    public function prevBtnText(string|Markup $text): self
    {
        $this->options['prevBtnText'] = $text;

        return $this;
    }

    public function doneBtnText(string|Markup $text): self
    {
        $this->options['doneBtnText'] = $text;

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
        return DriverOptions::escape($this->options);
    }

    public function isOnce(): bool
    {
        return $this->once;
    }
}
