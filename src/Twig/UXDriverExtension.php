<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Twig;

use Pentiminax\UX\Driver\Builder\HintsBuilder;
use Pentiminax\UX\Driver\Builder\TourBuilder;
use Pentiminax\UX\Driver\Model\Hints;
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
        private readonly HintsBuilder $hintsBuilder,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('create_tour', $this->createTour(...)),
            new TwigFunction('ux_tour', $this->renderTour(...), ['is_safe' => ['html']]),
            new TwigFunction('ux_highlight', $this->renderHighlight(...), ['is_safe' => ['html']]),
            new TwigFunction('create_hints', $this->createHints(...)),
            new TwigFunction('ux_hints', $this->renderHints(...), ['is_safe' => ['html']]),
            new TwigFunction('ux_driver_html', $this->trustedHtml(...)),
        ];
    }

    public function createTour(string $id): Tour
    {
        return $this->tourBuilder->create($id);
    }

    public function createHints(string $id): Hints
    {
        return $this->hintsBuilder->create($id);
    }

    public function renderHints(Hints $hints, bool $autostart = true): Markup
    {
        return $this->renderControllerAttributes([
            'id'        => $hints->id,
            'hints'     => $hints->getHints(),
            'options'   => $hints->getOptions(),
            'autostart' => $autostart,
        ], '@pentiminax/ux-driver/hints');
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
    private function renderControllerAttributes(array $values, string $controller = '@pentiminax/ux-driver/tour'): Markup
    {
        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
        $stimulusAttributes->addController($controller, $values);

        return new Markup((string) $stimulusAttributes, 'UTF-8');
    }
}
