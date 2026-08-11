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

        $this->assertStringContainsString('<header  data-pentiminax--ux-driver--tour-target="step" data-step-order="1" data-step-side="bottom" data-step-align="start" data-step-title="Header" data-step-description="Top bar" class="page-header">', $markup);
        $this->assertStringContainsString('<div  data-pentiminax--ux-driver--tour-target="step" data-step-order="2" data-step-side="right" data-step-align="end" data-step-title="Sidebar">', $markup);

        $this->assertStringContainsString('Mon en-tête', $markup);
        $this->assertStringContainsString('Ma sidebar', $markup);
    }

    /**
     * Everything the declarative mode cannot express as a dedicated attribute travels as a
     * single JSON payload, already nested the way driver.js expects it.
     */
    #[Test]
    public function it_renders_the_remaining_step_options_as_a_json_config(): void
    {
        $markup = $this->render(<<<'TWIG'
            <twig:Driver:Step title="Panier" popoverClass="promo" :showButtons="['next']" :advanceOnClick="true" :waitForElement="2000" :data="{tracking: 'cart'}" />
            TWIG);

        $this->assertSame([
            'popover'        => ['popoverClass' => 'promo', 'showButtons' => ['next']],
            'advanceOnClick' => true,
            'waitForElement' => 2000,
            'data'           => ['tracking' => 'cart'],
        ], json_decode($this->attribute($markup, 'data-step-config'), true, flags: \JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function it_omits_the_config_attribute_when_no_option_is_given(): void
    {
        $markup = $this->render('<twig:Driver:Step title="Header" />');

        $this->assertStringNotContainsString('data-step-config', $markup);
    }

    /**
     * A centered step has no target to highlight: rendering a real element would add an empty
     * box to the page, so the attributes ride on an inert `<template>` instead.
     */
    #[Test]
    public function it_renders_a_centered_step_as_an_inert_template(): void
    {
        $markup = $this->render('<twig:Driver:Step title="Bienvenue" :centered="true" />');

        $this->assertStringContainsString('<template ', $markup);
        $this->assertStringContainsString('</template>', $markup);
        $this->assertStringContainsString('data-step-centered="true"', $markup);
    }

    #[Test]
    public function it_escapes_the_step_button_labels_in_the_rendered_config(): void
    {
        $markup = $this->render(<<<'TWIG'
            <twig:Driver:Step title="Panier" nextBtnText="<img src=x onerror=alert(1)>" />
            TWIG);

        $this->assertStringNotContainsString('onerror=alert(1)>', $markup);
        $this->assertSame(
            '&lt;img src=x onerror=alert(1)&gt;',
            json_decode($this->attribute($markup, 'data-step-config'), true, flags: \JSON_THROW_ON_ERROR)['popover']['nextBtnText'],
        );
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

    /**
     * The whole point of escaping on the PHP side: the browser decodes the attribute once
     * through `dataset`, and driver.js hands the result to innerHTML. The rendered attribute
     * must therefore carry a doubly-encoded payload, so that one decoding still leaves inert
     * text.
     */
    #[Test]
    public function it_escapes_step_content_in_the_rendered_attributes(): void
    {
        $markup = $this->render(<<<'TWIG'
            <twig:Driver:Step title="<script>alert(1)</script>" description="<img src=x onerror=alert(1)>" />
            TWIG);

        $this->assertStringNotContainsString('<script>', $markup);
        $this->assertStringContainsString('data-step-title="&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;"', $markup);
        $this->assertStringContainsString('data-step-description="&amp;lt;img src=x onerror=alert(1)&amp;gt;"', $markup);

        // What the controller actually reads once the browser has decoded the attribute.
        $this->assertSame(
            '<script>alert(1)</script>',
            html_entity_decode(html_entity_decode($this->attribute($markup, 'data-step-title'), \ENT_QUOTES), \ENT_QUOTES),
        );
    }

    #[Test]
    public function it_keeps_trusted_markup_in_the_rendered_attributes(): void
    {
        $markup = $this->render(<<<'TWIG'
            <twig:Driver:Step :title="ux_driver_html('<b>Header</b>')" />
            TWIG);

        $this->assertSame('<b>Header</b>', $this->attribute($markup, 'data-step-title'));
    }

    #[Test]
    public function it_escapes_highlight_content(): void
    {
        $markup = $this->render(<<<'TWIG'
            <button {{ ux_highlight('.help', '<script>alert(1)</script>') }}></button>
            TWIG);

        $this->assertStringNotContainsString('<script>', $markup);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $this->firstStepTitle($markup));
    }

    #[Test]
    public function it_escapes_builder_tour_content(): void
    {
        $markup = $this->render(<<<'TWIG'
            {% set tour = create_tour('onboarding')
                .addStep('.header', '<script>alert(1)</script>')
                .addStep('.cta', ux_driver_html('<b>Action</b>')) %}
            <button {{ ux_tour(tour) }}></button>
            TWIG);

        $this->assertStringNotContainsString('<script>', $markup);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $this->firstStepTitle($markup));
        $this->assertSame('<b>Action</b>', $this->stepsPayload($markup)[1]['popover']['title']);
    }

    private function attribute(string $markup, string $name): string
    {
        if (1 !== preg_match('/'.preg_quote($name, '/').'="([^"]*)"/', $markup, $matches)) {
            self::fail(\sprintf('No "%s" attribute in: %s', $name, $markup));
        }

        return html_entity_decode($matches[1], \ENT_QUOTES);
    }

    /**
     * The payload the controller receives: the browser decodes the attribute once before
     * JSON.parse, which is exactly what html_entity_decode() reproduces here.
     *
     * @return array<int, array{popover: array<string, string>}>
     */
    private function stepsPayload(string $markup): array
    {
        $steps = json_decode($this->attribute($markup, 'data-pentiminax--ux-driver--tour-steps-value'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertIsArray($steps);

        return $steps;
    }

    private function firstStepTitle(string $markup): string
    {
        return $this->stepsPayload($markup)[0]['popover']['title'];
    }

    private function render(string $source): string
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');

        self::assertInstanceOf(Environment::class, $twig);

        return $twig->createTemplate($source)->render();
    }
}
