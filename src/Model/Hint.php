<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Normalizer;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Html\DriverOptions;
use Pentiminax\UX\Driver\Html\PopoverContent;
use Twig\Markup;

/**
 * @phpstan-type BeaconOptions array{
 *     side?: Side|string,
 *     align?: Align|string,
 *     animate?: bool,
 *     className?: string,
 * }
 * @phpstan-type HintOptions array{
 *     popoverClass?: string,
 *     showButton?: bool,
 *     buttonText?: string|Markup,
 *     beacon?: BeaconOptions,
 *     data?: array<string, mixed>,
 * }
 */
final readonly class Hint
{
    /** Keys nested under `beacon`: the dot driver.js paints next to the element. */
    public const BEACON_OPTIONS = [
        'side'      => Normalizer::Side,
        'align'     => Normalizer::Align,
        'animate'   => null,
        'className' => null,
    ];

    /**
     * Where each option lands in the driver.js hint payload, and how its value is normalized.
     * The ones reaching innerHTML are escaped by DriverOptions, not listed here.
     */
    private const SCHEMA = [
        'popover' => [
            'popoverClass' => null,
            'showButton'   => null,
            'buttonText'   => null,
        ],
        'hint' => [
            'beacon' => self::BEACON_OPTIONS,
            'data'   => null,
        ],
    ];

    private string $side;

    private string $align;

    /** @var array<string, mixed> */
    private array $popoverOptions;

    /** @var array<string, mixed> */
    private array $beaconOptions;

    /** @var array<string, mixed> */
    private array $hintOptions;

    /**
     * @param string|null $element a CSS selector, or null when the element is the DOM node
     *                             carrying the hint — what the declarative mode does
     * @param string|null $id      a stable identifier for `open()`, `dismiss()` and `restore()`;
     *                             driver.js falls back to the hint index when it is omitted
     * @param HintOptions $options
     *
     * @throws \InvalidArgumentException when an option is not a serializable driver.js hint option
     * @throws \ValueError               when a side or alignment value is unknown
     */
    public function __construct(
        private ?string $element = null,
        private ?string $id = null,
        private string|Markup|null $title = null,
        private string|Markup|null $description = null,
        Side|string $side = Side::Bottom,
        Align|string $align = Align::Start,
        array $options = [],
    ) {
        $this->side  = Side::normalize($side);
        $this->align = Align::normalize($align);

        ['popover' => $popover, 'hint' => $hint] = Options::split($options, self::SCHEMA, 'hint');

        /** @var array<string, mixed> $beacon */
        $beacon = $hint['beacon'] ?? [];
        unset($hint['beacon']);

        $this->popoverOptions = DriverOptions::escape($popover);
        $this->beaconOptions  = $beacon;
        $this->hintOptions    = $hint;
    }

    public function getElement(): ?string
    {
        return $this->element;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * The escaped popover content the declarative mode renders as its own `data-hint-*`
     * attribute: the controller decodes it once through `dataset` before driver.js hands it to
     * innerHTML. The builder mode gets the same treatment through toArray().
     */
    public function titleHtml(): ?string
    {
        return PopoverContent::render($this->title);
    }

    public function descriptionHtml(): ?string
    {
        return PopoverContent::render($this->description);
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
        return array_filter([
            'element' => $this->element,
            'id'      => $this->id,
            'beacon'  => [] === $this->beaconOptions ? null : $this->beaconOptions,
            'popover' => Options::popover($this->title, $this->description, $this->side, $this->align, $this->popoverOptions),
        ], static fn ($v) => null !== $v) + $this->hintOptions;
    }

    /**
     * Everything the declarative mode cannot express through a dedicated `data-hint-*`
     * attribute: the nested driver.js payload without element, id, title, description, side
     * and align.
     *
     * @return array<string, mixed>
     */
    public function extras(): array
    {
        return array_filter([
            'beacon'  => [] === $this->beaconOptions ? null : $this->beaconOptions,
            'popover' => [] === $this->popoverOptions ? null : $this->popoverOptions,
        ], static fn ($v) => null !== $v) + $this->hintOptions;
    }
}
