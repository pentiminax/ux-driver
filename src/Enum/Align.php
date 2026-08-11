<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Enum;

/**
 * How the popover is aligned along the side it is placed on.
 */
enum Align: string
{
    case Start = 'start';

    case Center = 'center';

    case End = 'end';

    /**
     * @throws \ValueError when the alignment is not one of `start`, `center`, `end`
     */
    public static function normalize(self|string $align): string
    {
        return ($align instanceof self ? $align : self::from($align))->value;
    }
}
