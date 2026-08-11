<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Driver:Step')]
final class Step
{
    public int $order = 0;

    public string $title;

    public ?string $description = null;

    public string $side = 'bottom';

    public string $align = 'start';

    public string $tag = 'div';
}
