<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Enum;

/**
 * Which side of the highlighted element the popover is placed on.
 */
enum Side: string
{
    case Top = 'top';

    case Right = 'right';

    case Bottom = 'bottom';

    case Left = 'left';

    /**
     * @throws \ValueError when the side is not one of `top`, `right`, `bottom`, `left`
     */
    public static function normalize(self|string $side): string
    {
        return ($side instanceof self ? $side : self::from($side))->value;
    }
}
