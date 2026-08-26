<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Html;

use Twig\Markup;

/**
 * The single list of driver.js fields written with innerHTML, and the one pass that escapes
 * them. Every authoring mode — Twig components, `create_tour()`, the PHP builders,
 * `ux_highlight()` — serializes its options through here, so adding an option that reaches
 * innerHTML is a one-line change.
 */
final class DriverOptions
{
    /**
     * `title`, `description` and the button labels are written verbatim by driver.js;
     * `progressText` is written after `{{current}}` and `{{total}}` have been substituted, so
     * it is a sink too.
     *
     * @var list<string>
     */
    public const HTML_SINKS = [
        'title',
        'description',
        'progressText',
        'nextBtnText',
        'prevBtnText',
        'doneBtnText',
        'buttonText',
    ];

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public static function escape(array $options): array
    {
        foreach (self::HTML_SINKS as $key) {
            if (!\array_key_exists($key, $options)) {
                continue;
            }

            $value = $options[$key];
            \assert(null === $value || \is_string($value) || $value instanceof Markup);

            $options[$key] = PopoverContent::render($value);
        }

        return $options;
    }
}
