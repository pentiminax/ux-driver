<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Pentiminax\UX\Driver\Html\PopoverContent;
use Pentiminax\UX\Driver\Model\Hint as HintModel;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Twig\Markup;

#[AsTwigComponent('Driver:Hint')]
final class Hint
{
    /**
     * A stable identifier for the `open`, `dismiss` and `restore` actions. driver.js falls back
     * to the hint index when it is omitted.
     */
    public ?string $hintId = null;

    public string|Markup|null $title = null;

    public string|Markup|null $description = null;

    public string $side = 'bottom';

    public string $align = 'start';

    public string $tag = 'div';

    /** @var array<string, mixed>|null */
    public ?array $beacon = null;

    public ?string $popoverClass = null;

    public ?bool $showButton = null;

    public string|Markup|null $buttonText = null;

    /** @var array<string, mixed>|null */
    public ?array $data = null;

    /**
     * The template must render these, not the raw properties: the controller decodes the
     * attribute once through `dataset` before handing it to innerHTML.
     */
    public function titleHtml(): ?string
    {
        return PopoverContent::render($this->title);
    }

    public function descriptionHtml(): ?string
    {
        return PopoverContent::render($this->description);
    }

    /**
     * @throws \ValueError when the side is unknown
     */
    public function sideValue(): string
    {
        return $this->model()->getSide();
    }

    /**
     * @throws \ValueError when the alignment is unknown
     */
    public function alignValue(): string
    {
        return $this->model()->getAlign();
    }

    /**
     * The remaining driver.js hint options, already nested the way the controller hands them to
     * driver.js.
     *
     * @throws \InvalidArgumentException when an option is not a serializable driver.js hint option
     */
    public function configJson(): ?string
    {
        $extras = $this->model()->extras();

        return [] === $extras ? null : json_encode($extras, \JSON_THROW_ON_ERROR);
    }

    private function model(): HintModel
    {
        return new HintModel(
            element: '',
            side: $this->side,
            align: $this->align,
            options: $this->options(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return array_filter([
            'popoverClass' => $this->popoverClass,
            'showButton'   => $this->showButton,
            'buttonText'   => $this->buttonText,
            'beacon'       => $this->beacon,
            'data'         => $this->data,
        ], static fn ($value) => null !== $value);
    }
}
