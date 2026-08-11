<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Enum;

/**
 * driver.js `AllowedButtons`.
 */
enum Button: string
{
    case Next = 'next';

    case Previous = 'previous';

    case Close = 'close';

    /**
     * @param iterable<self|string> $buttons
     *
     * @return list<string>
     */
    public static function normalizeAll(iterable $buttons): array
    {
        $names = [];

        foreach ($buttons as $button) {
            $names[] = ($button instanceof self ? $button : self::from($button))->value;
        }

        return $names;
    }
}
