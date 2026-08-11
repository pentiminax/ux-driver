<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Builder;

use Pentiminax\UX\Driver\Model\Tour;

final class TourBuilder
{
    public function create(string $id): Tour
    {
        return new Tour($id);
    }
}
