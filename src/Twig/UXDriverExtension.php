<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig;

use Pentiminax\UX\Driver\Builder\TourBuilder;
use Pentiminax\UX\Driver\Model\Step;
use Pentiminax\UX\Driver\Model\Tour;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class UXDriverExtension extends AbstractExtension
{
    public function __construct(
        private readonly StimulusHelper $stimulus,
        private readonly TourBuilder $tourBuilder,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('create_tour', $this->createTour(...)),
            new TwigFunction('ux_tour', $this->renderTour(...), ['is_safe' => ['html']]),
            new TwigFunction('ux_highlight', $this->renderHighlight(...), ['is_safe' => ['html']]),
            new TwigFunction('ux_driver_html', $this->trustedHtml(...)),
        ];
    }

    public function createTour(string $id): Tour
    {
        return $this->tourBuilder->create($id);
    }

    public function renderTour(Tour $tour): Markup
    {
        return $this->renderControllerAttributes([
            'id'        => $tour->id,
            'steps'     => $tour->getSteps(),
            'options'   => $tour->getOptions(),
            'once'      => $tour->isOnce(),
            'autostart' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderHighlight(
        string $element,
        string|Markup $title,
        string|Markup|null $description = null,
        string $side = 'bottom',
        string $align = 'start',
        array $options = [],
    ): Markup {
        $step = new Step($element, $title, $description, $side, $align);

        return $this->renderControllerAttributes([
            'id'        => 'highlight-'.md5($element.$title),
            'steps'     => [$step->toArray()],
            'options'   => $options,
            'once'      => false,
            'autostart' => false,
        ]);
    }

    /**
     * Marks trusted markup so it survives the popover escaping.
     */
    public function trustedHtml(string $html): Markup
    {
        return new Markup($html, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $values
     */
    private function renderControllerAttributes(array $values): Markup
    {
        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
        $stimulusAttributes->addController('@pentiminax/ux-driver/tour', $values);

        return new Markup((string) $stimulusAttributes, 'UTF-8');
    }
}
