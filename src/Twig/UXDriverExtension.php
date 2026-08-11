<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig;

use Pentiminax\UX\Driver\Builder\TourBuilder;
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
        string $title,
        ?string $description = null,
        string $side = 'bottom',
        string $align = 'start',
        array $options = [],
    ): Markup {
        return $this->renderControllerAttributes([
            'id'        => 'highlight-' . md5($element . $title),
            'steps'     => [[
                'element' => $element,
                'popover' => array_filter([
                    'title'       => $title,
                    'description' => $description,
                    'side'        => $side,
                    'align'       => $align,
                ], static fn ($value) => null !== $value),
            ]],
            'options'   => $options,
            'once'      => false,
            'autostart' => false,
        ]);
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
