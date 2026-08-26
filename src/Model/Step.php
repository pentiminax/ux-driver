<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\Normalizer;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Html\DriverOptions;
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
     * Where each option lands in the driver.js step payload, and how its value is normalized.
     * The ones reaching innerHTML are escaped by DriverOptions, not listed here.
     */
    private const SCHEMA = [
        'popover' => [
            'popoverClass'   => null,
            'showButtons'    => Normalizer::Buttons,
            'disableButtons' => Normalizer::Buttons,
            'showProgress'   => null,
            'progressText'   => null,
            'nextBtnText'    => null,
            'prevBtnText'    => null,
            'doneBtnText'    => null,
        ],
        'step' => [
            'disableActiveInteraction' => null,
            'advanceOnClick'           => null,
            'skipMissingElement'       => null,
            'waitForElement'           => null,
            'data'                     => null,
        ],
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

        ['popover' => $popover, 'step' => $step] = Options::split($options, self::SCHEMA, 'step');

        $this->popoverOptions = DriverOptions::escape($popover);
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
        // A centered step has no element: driver.js then places the popover in the middle of
        // the screen instead of anchoring it.
        return array_filter([
            'element' => $this->element,
            'popover' => Options::popover($this->title, $this->description, $this->side, $this->align, $this->popoverOptions),
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
}
