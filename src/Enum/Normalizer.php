<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Enum;

/**
 * How an option value is turned into what driver.js expects. The option tables of Step, Hint,
 * Tour and Hints map an option name to one of these; an option mapped to null travels as it
 * was given.
 *
 * A pure enum, not a backed one: these cases name a behaviour, and nothing serializes them.
 */
enum Normalizer
{
    /** driver.js `AllowedButtons`, from one button or an iterable of them. */
    case Buttons;

    case Side;

    case Align;

    case OverlayClick;

    /**
     * @throws \ValueError when the value is not one this normalizer accepts
     */
    public function apply(mixed $value): mixed
    {
        if (self::Buttons === $this) {
            /** @var iterable<Button|string> $buttons */
            $buttons = is_iterable($value) ? $value : [$value];

            return Button::normalizeAll($buttons);
        }

        if (self::Side === $this) {
            \assert(\is_string($value) || $value instanceof Side);

            return Side::normalize($value);
        }

        if (self::Align === $this) {
            \assert(\is_string($value) || $value instanceof Align);

            return Align::normalize($value);
        }

        \assert(\is_string($value) || $value instanceof OverlayClickBehavior);

        return ($value instanceof OverlayClickBehavior ? $value : OverlayClickBehavior::from($value))->value;
    }
}
