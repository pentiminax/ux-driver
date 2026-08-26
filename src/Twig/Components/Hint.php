<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig\Components;

use Pentiminax\UX\Driver\Model\Hint as HintModel;
use Pentiminax\UX\Driver\StimulusContract;
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
     * The target declaration and the `data-hint-*` attributes the hints controller reads back
     * through `dataset`, derived from StimulusContract so an attribute renamed on the JS side
     * cannot keep rendering here.
     *
     * Everything comes from the model the builder mode uses, so both modes validate, escape and
     * shape their payload identically. The model carries no element: in the declarative mode the
     * hint is the DOM node itself, which the controller supplies.
     *
     * @return array<string, string>
     *
     * @throws \InvalidArgumentException when an option is not a serializable driver.js hint option
     * @throws \ValueError               when a side or an alignment is unknown
     */
    public function stimulusAttributes(): array
    {
        $hint = $this->model();

        return StimulusContract::targetAttributes(StimulusContract::HINTS, [
            'side'        => $hint->getSide(),
            'align'       => $hint->getAlign(),
            'id'          => $hint->getId(),
            'title'       => $hint->titleHtml(),
            'description' => $hint->descriptionHtml(),
            'config'      => $this->configJson($hint),
        ]);
    }

    /**
     * The remaining driver.js hint options, already nested the way the controller hands them to
     * driver.js.
     */
    private function configJson(HintModel $hint): ?string
    {
        $extras = $hint->extras();

        return [] === $extras ? null : json_encode($extras, \JSON_THROW_ON_ERROR);
    }

    private function model(): HintModel
    {
        return new HintModel(
            id: $this->hintId,
            title: $this->title,
            description: $this->description,
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
