<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Pentiminax\UX\Driver\Html\PopoverContent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Twig\Markup;

#[AsTwigComponent('Driver:Step')]
final class Step
{
    public int $order = 0;

    public string|Markup $title;

    public string|Markup|null $description = null;

    public string $side = 'bottom';

    public string $align = 'start';

    public string $tag = 'div';

    /**
     * The template must render these, not the raw properties: the controller decodes the
     * attribute once through `dataset` before handing it to innerHTML.
     */
    public function titleHtml(): string
    {
        return (string) PopoverContent::render($this->title);
    }

    public function descriptionHtml(): ?string
    {
        return PopoverContent::render($this->description);
    }
}
