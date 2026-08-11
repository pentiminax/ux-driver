<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Html\PopoverContent;
use Twig\Markup;

/**
 * @phpstan-type StepOptions array{
 *     popoverClass?: string,
 *     showButtons?: list<Button|string>,
 *     disableButtons?: list<Button|string>,
 *     showProgress?: bool,
 *     progressText?: string|Markup,
 *     nextBtnText?: string|Markup,
 *     prevBtnText?: string|Markup,
 *     doneBtnText?: string|Markup,
 *     disableActiveInteraction?: bool,
 *     advanceOnClick?: bool,
 *     skipMissingElement?: bool,
 *     waitForElement?: int,
 *     data?: array<string, mixed>,
 * }
 */
final readonly class Step
{
    /**
     * Keys nested under `popover` in the driver.js payload, mapped to how their value is
     * normalized. The button labels and the progress text are written with innerHTML, like the
     * title and the description, so they go through the same escaping.
     */
    private const POPOVER_OPTIONS = [
        'popoverClass'   => null,
        'showButtons'    => 'buttons',
        'disableButtons' => 'buttons',
        'showProgress'   => null,
        'progressText'   => 'html',
        'nextBtnText'    => 'html',
        'prevBtnText'    => 'html',
        'doneBtnText'    => 'html',
    ];

    /** Keys living at the root of the driver.js step payload. */
    private const STEP_OPTIONS = [
        'disableActiveInteraction',
        'advanceOnClick',
        'skipMissingElement',
        'waitForElement',
        'data',
    ];

    private string $side;

    private string $align;

    /** @var array<string, mixed> */
    private array $popoverOptions;

    /** @var array<string, mixed> */
    private array $stepOptions;

    /**
     * @param string|null $element a CSS selector, or null for a step centered on the screen
     * @param StepOptions $options
     *
     * @throws \InvalidArgumentException when an option is not a serializable driver.js step option
     * @throws \ValueError               when a button, side or alignment value is unknown
     */
    public function __construct(
        private ?string $element = null,
        private string|Markup|null $title = null,
        private string|Markup|null $description = null,
        Side|string $side = Side::Bottom,
        Align|string $align = Align::Start,
        array $options = [],
    ) {
        $this->side  = Side::normalize($side);
        $this->align = Align::normalize($align);

        $popover = [];
        $step    = [];

        foreach ($options as $key => $value) {
            if (\array_key_exists($key, self::POPOVER_OPTIONS)) {
                $popover[$key] = self::normalizeOption(self::POPOVER_OPTIONS[$key], $value);

                continue;
            }

            if (\in_array($key, self::STEP_OPTIONS, true)) {
                $step[$key] = $value;

                continue;
            }

            throw new \InvalidArgumentException(\sprintf('Unknown step option "%s". Serializable driver.js step options are: %s.', $key, implode(', ', [...array_keys(self::POPOVER_OPTIONS), ...self::STEP_OPTIONS])));
        }

        $this->popoverOptions = $popover;
        $this->stepOptions    = $step;
    }

    public function getElement(): ?string
    {
        return $this->element;
    }

    public function getTitle(): string|Markup|null
    {
        return $this->title;
    }

    public function getDescription(): string|Markup|null
    {
        return $this->description;
    }

    public function getSide(): string
    {
        return $this->side;
    }

    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $popover = array_filter([
            'title'       => PopoverContent::render($this->title),
            'description' => PopoverContent::render($this->description),
            'side'        => $this->side,
            'align'       => $this->align,
        ], static fn ($v) => null !== $v) + $this->popoverOptions;

        // A centered step has no element: driver.js then places the popover in the middle of
        // the screen instead of anchoring it.
        return array_filter([
            'element' => $this->element,
            'popover' => $popover,
        ], static fn ($v) => null !== $v) + $this->stepOptions;
    }

    /**
     * Everything the declarative mode cannot express through a dedicated `data-step-*`
     * attribute: the nested driver.js payload without element, title, description, side and
     * align.
     *
     * @return array<string, mixed>
     */
    public function extras(): array
    {
        return array_filter([
            'popover' => [] === $this->popoverOptions ? null : $this->popoverOptions,
        ], static fn ($v) => null !== $v) + $this->stepOptions;
    }

    private static function normalizeOption(?string $kind, mixed $value): mixed
    {
        if ('buttons' === $kind) {
            /** @var iterable<Button|string> $buttons */
            $buttons = is_iterable($value) ? $value : [$value];

            return Button::normalizeAll($buttons);
        }

        if ('html' === $kind) {
            \assert(null === $value || \is_string($value) || $value instanceof Markup);

            return PopoverContent::render($value);
        }

        return $value;
    }
}
