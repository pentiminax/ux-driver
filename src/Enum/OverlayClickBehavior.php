<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Enum;

/**
 * driver.js also accepts a callback here; callbacks are not serializable, so only the two
 * documented string behaviours are exposed. Use the `ux-driver:*` events for custom logic.
 */
enum OverlayClickBehavior: string
{
    case Close = 'close';

    case NextStep = 'nextStep';
}
