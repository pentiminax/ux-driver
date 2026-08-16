<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Twig;

use Pentiminax\UX\Driver\Builder\HintsBuilder;
use Pentiminax\UX\Driver\Builder\TourBuilder;
use Pentiminax\UX\Driver\Model\Hints;
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
        $tour = (new Tour('onboarding'))
            ->addStep('.header', 'Bienvenue')
            ->once();

        $markup = (string) $this->extension()->renderTour($tour);

        $this->assertStringContainsString('data-controller="pentiminax--ux-driver--tour"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-id-value="onboarding"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-once-value="true"', $markup);
        $this->assertStringContainsString('Bienvenue', $markup);
    }

    #[Test]
    public function it_renders_stimulus_attributes_for_a_highlight(): void
    {
        $markup = (string) $this->extension()->renderHighlight('.help-button', 'Aide', 'Cliquez ici pour commencer');

        $this->assertStringContainsString('data-controller="pentiminax--ux-driver--tour"', $markup);
        $this->assertStringContainsString('.help-button', $markup);
        $this->assertStringContainsString('Aide', $markup);
        $this->assertStringContainsString('Cliquez ici pour commencer', $markup);
    }

    #[Test]
    public function it_creates_tours_from_the_builder_factory(): void
    {
        $tour = $this->extension()->createTour('dashboard');

        $this->assertSame('dashboard', $tour->id);
    }

    #[Test]
    public function it_renders_stimulus_attributes_for_a_hint_group(): void
    {
        $hints = (new Hints('help'))
            ->addHint('.export', 'export', 'Exporter', 'Téléchargez vos données')
            ->overlay();

        $markup = (string) $this->extension()->renderHints($hints);

        $this->assertStringContainsString('data-controller="pentiminax--ux-driver--hints"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--hints-id-value="help"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--hints-autostart-value="true"', $markup);
        $this->assertStringContainsString('.export', $markup);
        $this->assertStringContainsString('Exporter', $markup);
    }

    #[Test]
    public function it_creates_hint_groups_from_the_builder_factory(): void
    {
        $hints = $this->extension()->createHints('help');

        $this->assertSame('help', $hints->id);
    }

    #[Test]
    public function it_renders_a_tour_start_action(): void
    {
        $markup = (string) $this->extension()->renderTourAction('start');

        $this->assertSame('data-action="pentiminax--ux-driver--tour#start"', $markup);
    }

    #[Test]
    public function it_renders_a_tour_action_with_params(): void
    {
        $markup = (string) $this->extension()->renderTourAction('moveTo', ['index' => 2]);

        $this->assertStringContainsString('data-action="pentiminax--ux-driver--tour#moveTo"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-index-param="2"', $markup);
    }

    #[Test]
    public function it_rejects_an_unknown_tour_action(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown tour action "pause"');

        $this->extension()->renderTourAction('pause');
    }

    #[Test]
    public function it_renders_a_hints_action(): void
    {
        $markup = (string) $this->extension()->renderHintsAction('show');

        $this->assertSame('data-action="pentiminax--ux-driver--hints#show"', $markup);
    }

    #[Test]
    public function it_renders_a_hints_action_with_params(): void
    {
        $markup = (string) $this->extension()->renderHintsAction('open', ['hintId' => 'export']);

        $this->assertStringContainsString('data-action="pentiminax--ux-driver--hints#open"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--hints-hint-id-param="export"', $markup);
    }

    #[Test]
    public function it_rejects_an_unknown_hints_action(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown hints action "pause"');

        $this->extension()->renderHintsAction('pause');
    }

    private function extension(): UXDriverExtension
    {
        return new UXDriverExtension(
            new StimulusHelper(new Environment(new ArrayLoader())),
            new TourBuilder(),
            new HintsBuilder(),
        );
    }
}
