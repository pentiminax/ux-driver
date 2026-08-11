<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Twig;

use Pentiminax\UX\Driver\Builder\TourBuilder;
use Pentiminax\UX\Driver\Model\Tour;
use Pentiminax\UX\Driver\Twig\UXDriverExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
#[CoversClass(UXDriverExtension::class)]
final class UXDriverExtensionTest extends TestCase
{
    #[Test]
    public function it_renders_stimulus_attributes_for_a_builder_tour(): void
    {
        $extension = new UXDriverExtension(new StimulusHelper(new Environment(new ArrayLoader())), new TourBuilder());

        $tour = (new Tour('onboarding'))
            ->addStep('.header', 'Bienvenue')
            ->once();

        $markup = (string) $extension->renderTour($tour);

        $this->assertStringContainsString('data-controller="pentiminax--ux-driver--tour"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-id-value="onboarding"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-once-value="true"', $markup);
        $this->assertStringContainsString('Bienvenue', $markup);
    }

    #[Test]
    public function it_renders_stimulus_attributes_for_a_highlight(): void
    {
        $extension = new UXDriverExtension(new StimulusHelper(new Environment(new ArrayLoader())), new TourBuilder());

        $markup = (string) $extension->renderHighlight('.help-button', 'Aide', 'Cliquez ici pour commencer');

        $this->assertStringContainsString('data-controller="pentiminax--ux-driver--tour"', $markup);
        $this->assertStringContainsString('.help-button', $markup);
        $this->assertStringContainsString('Aide', $markup);
        $this->assertStringContainsString('Cliquez ici pour commencer', $markup);
    }

    #[Test]
    public function it_creates_tours_from_the_builder_factory(): void
    {
        $extension = new UXDriverExtension(new StimulusHelper(new Environment(new ArrayLoader())), new TourBuilder());

        $tour = $extension->createTour('dashboard');

        $this->assertSame('dashboard', $tour->id);
    }
}
