<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Html;

use Twig\Markup;

/**
 * driver.js writes popover titles and descriptions with innerHTML, so any value coming from
 * the database or from user input would be executed as markup. Twig's own attribute escaping
 * does not protect here: the controller reads the value through `dataset`, which decodes it
 * once, and hands it straight to innerHTML.
 *
 * Escaping therefore happens on the PHP side, where the builder payload and the declarative
 * `data-step-*` attributes both originate — one treatment point for both modes.
 *
 * Opt out with `Twig\Markup` (`ux_driver_html()` in Twig) when the markup is trusted.
 */
final class PopoverContent
{
    public static function render(string|Markup|null $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Markup) {
            return (string) $value;
        }

        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
