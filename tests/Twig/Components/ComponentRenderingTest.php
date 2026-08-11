<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Twig\Components;

use Pentiminax\UX\Driver\Twig\Components\Step;
use Pentiminax\UX\Driver\Twig\Components\Tour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Renders the components through a real kernel: the Stimulus values the
 * controller reads live in the templates, not in the PHP classes, so asserting
 * on options() alone leaves the whole PHP↔Stimulus boundary untested.
 *
 * @internal
 */
#[CoversClass(Tour::class)]
#[CoversClass(Step::class)]
final class ComponentRenderingTest extends KernelTestCase
{
    #[Test]
    public function it_renders_the_tour_stimulus_values(): void
    {
        $markup = $this->render(<<<'TWIG'
            <twig:Driver:Tour id="onboarding" :autostart="true" :once="true" overlayColor="#111111" :stagePadding="12" />
            TWIG);

        $this->assertStringContainsString('data-controller="pentiminax--ux-driver--tour"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-id-value="onboarding"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-autostart-value="true"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-once-value="true"', $markup);
        $this->assertStringContainsString(
            'data-pentiminax--ux-driver--tour-options-value="{"showProgress":true,"animate":true,"smoothScroll":false,"allowClose":true,"overlayColor":"#111111","stagePadding":12}"',
            html_entity_decode($markup, \ENT_QUOTES),
        );
    }

    #[Test]
    public function it_renders_default_tour_stimulus_values(): void
    {
        $markup = $this->render('<twig:Driver:Tour id="dashboard" />');

        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-autostart-value="false"', $markup);
        $this->assertStringContainsString('data-pentiminax--ux-driver--tour-once-value="false"', $markup);
        $this->assertStringContainsString(
            'data-pentiminax--ux-driver--tour-options-value="{"showProgress":true,"animate":true,"smoothScroll":false,"allowClose":true}"',
            html_entity_decode($markup, \ENT_QUOTES),
        );
    }

    #[Test]
    public function it_renders_nested_steps_as_controller_targets(): void
    {
        $markup = $this->render(<<<'TWIG'
            <twig:Driver:Tour id="onboarding">
                <twig:Driver:Step :order="1" title="Header" description="Top bar" tag="header" class="page-header">Mon en-tête</twig:Driver:Step>
                <twig:Driver:Step :order="2" title="Sidebar" side="right" align="end">Ma sidebar</twig:Driver:Step>
            </twig:Driver:Tour>
            TWIG);

        $this->assertSame(2, substr_count($markup, 'data-pentiminax--ux-driver--tour-target="step"'));

        $this->assertStringContainsString('<header  data-pentiminax--ux-driver--tour-target="step" data-step-order="1" data-step-title="Header" data-step-side="bottom" data-step-align="start" data-step-description="Top bar" class="page-header">', $markup);
        $this->assertStringContainsString('<div  data-pentiminax--ux-driver--tour-target="step" data-step-order="2" data-step-title="Sidebar" data-step-side="right" data-step-align="end">', $markup);

        $this->assertStringContainsString('Mon en-tête', $markup);
        $this->assertStringContainsString('Ma sidebar', $markup);
    }

    /**
     * A step without a description used to blow up: ComponentAttributes rejects
     * null values, so the documented `<twig:Driver:Step title="…" />` usage never
     * rendered.
     */
    #[Test]
    public function it_omits_the_description_attribute_when_no_description_is_given(): void
    {
        $markup = $this->render('<twig:Driver:Step title="Header" />');

        $this->assertStringContainsString('data-step-title="Header"', $markup);
        $this->assertStringNotContainsString('data-step-description', $markup);
    }

    private function render(string $source): string
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');

        self::assertInstanceOf(Environment::class, $twig);

        return $twig->createTemplate($source)->render();
    }
}
