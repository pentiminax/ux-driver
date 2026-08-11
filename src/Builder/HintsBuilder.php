<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Builder;

use Pentiminax\UX\Driver\Model\Hints;

final class HintsBuilder
{
    public function create(string $id): Hints
    {
        return new Hints($id);
    }
}
