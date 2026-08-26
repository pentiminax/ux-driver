<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Html\DriverOptions;
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
    /**
     * Keys nested under `popover` in the driver.js hint payload. The ones reaching innerHTML
     * are escaped by DriverOptions, not listed here.
     */
    private const POPOVER_OPTIONS = [
        'popoverClass' => null,
        'showButton'   => null,
        'buttonText'   => null,
    ];

    /** Keys nested under `beacon`: the dot driver.js paints next to the element. */
    private const BEACON_OPTIONS = [
        'side'      => 'side',
        'align'     => 'align',
        'animate'   => null,
        'className' => null,
    ];

    /** Keys living at the root of the driver.js hint payload. */
    private const HINT_OPTIONS = ['data'];

    private string $side;

    private string $align;

    /** @var array<string, mixed> */
    private array $popoverOptions;

    /** @var array<string, mixed> */
    private array $beaconOptions;

    /** @var array<string, mixed> */
    private array $hintOptions;

    /**
     * @param string      $element a CSS selector
     * @param string|null $id      a stable identifier for `open()`, `dismiss()` and `restore()`;
     *                             driver.js falls back to the hint index when it is omitted
     * @param HintOptions $options
     *
     * @throws \InvalidArgumentException when an option is not a serializable driver.js hint option
     * @throws \ValueError               when a side or alignment value is unknown
     */
    public function __construct(
        private string $element,
        private ?string $id = null,
        private string|Markup|null $title = null,
        private string|Markup|null $description = null,
        Side|string $side = Side::Bottom,
        Align|string $align = Align::Start,
        array $options = [],
    ) {
        $this->side  = Side::normalize($side);
        $this->align = Align::normalize($align);

        $popover = [];
        $hint    = [];
        $beacon  = [];

        foreach ($options as $key => $value) {
            if (\array_key_exists($key, self::POPOVER_OPTIONS)) {
                $popover[$key] = self::normalizeOption(self::POPOVER_OPTIONS[$key], $value);

                continue;
            }

            if ('beacon' === $key) {
                $beacon = self::normalizeBeacon($value);

                continue;
            }

            if (\in_array($key, self::HINT_OPTIONS, true)) {
                $hint[$key] = $value;

                continue;
            }

            throw new \InvalidArgumentException(\sprintf('Unknown hint option "%s". Serializable driver.js hint options are: %s.', $key, implode(', ', [...array_keys(self::POPOVER_OPTIONS), 'beacon', ...self::HINT_OPTIONS])));
        }

        $this->popoverOptions = DriverOptions::escape($popover);
        $this->beaconOptions  = $beacon;
        $this->hintOptions    = $hint;
    }

    public function getElement(): string
    {
        return $this->element;
    }

    public function getId(): ?string
    {
        return $this->id;
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
        $popover = array_filter(DriverOptions::escape([
            'title'       => $this->title,
            'description' => $this->description,
        ]) + [
            'side'  => $this->side,
            'align' => $this->align,
        ], static fn ($v) => null !== $v) + $this->popoverOptions;

        return array_filter([
            'element' => $this->element,
            'id'      => $this->id,
            'beacon'  => [] === $this->beaconOptions ? null : $this->beaconOptions,
            'popover' => $popover,
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

    /**
     * @param array<string, mixed> $beacon
     *
     * @return array<string, mixed>
     */
    private static function normalizeBeacon(array $beacon): array
    {
        $normalized = [];

        foreach ($beacon as $key => $value) {
            if (!\array_key_exists($key, self::BEACON_OPTIONS)) {
                throw new \InvalidArgumentException(\sprintf('Unknown beacon option "%s". driver.js beacon options are: %s.', $key, implode(', ', array_keys(self::BEACON_OPTIONS))));
            }

            $normalized[$key] = self::normalizeOption(self::BEACON_OPTIONS[$key], $value);
        }

        return $normalized;
    }

    private static function normalizeOption(?string $kind, mixed $value): mixed
    {
        if ('side' === $kind) {
            \assert(\is_string($value) || $value instanceof Side);

            return Side::normalize($value);
        }

        if ('align' === $kind) {
            \assert(\is_string($value) || $value instanceof Align);

            return Align::normalize($value);
        }

        return $value;
    }
}
