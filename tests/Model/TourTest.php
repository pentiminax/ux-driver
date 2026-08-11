<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Model;

use Pentiminax\UX\Driver\Model\Step;
use Pentiminax\UX\Driver\Model\Tour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

/**
 * @internal
 */
#[CoversClass(Step::class)]
#[CoversClass(Tour::class)]
final class TourTest extends TestCase
{
    #[Test]
    public function it_builds_steps_and_options_fluently(): void
    {
        $tour = (new Tour('onboarding'))
            ->addStep('.header', 'Bienvenue', 'En-tête de page', 'bottom', 'start')
            ->addStep('.sidebar', 'Navigation', side: 'right')
            ->showProgress()
            ->animate(false)
            ->smoothScroll()
            ->allowClose(false)
            ->overlayColor('#000000')
            ->overlayOpacity(0.75)
            ->stagePadding(8)
            ->once();

        $this->assertSame('onboarding', $tour->id);
        $this->assertTrue($tour->isOnce());
        $this->assertSame([
            [
                'element' => '.header',
                'popover' => [
                    'title'       => 'Bienvenue',
                    'description' => 'En-tête de page',
                    'side'        => 'bottom',
                    'align'       => 'start',
                ],
            ],
            [
                'element' => '.sidebar',
                'popover' => [
                    'title' => 'Navigation',
                    'side'  => 'right',
                    'align' => 'start',
                ],
            ],
        ], $tour->getSteps());
        $this->assertSame([
            'showProgress'   => true,
            'animate'        => false,
            'smoothScroll'   => true,
            'allowClose'     => false,
            'overlayColor'   => '#000000',
            'overlayOpacity' => 0.75,
            'stagePadding'   => 8,
        ], $tour->getOptions());
    }

    /**
     * driver.js renders the popover with innerHTML, so an unescaped payload is executable
     * markup. The escaping happens once here; the browser decodes it once through `dataset`.
     */
    #[Test]
    public function it_escapes_popover_content_in_the_step_payload(): void
    {
        $step = new Step(
            '.cta',
            '<script>alert(1)</script>',
            '<img src=x onerror="alert(\'xss\')"> l\'"aide"',
        );

        $this->assertSame([
            'element' => '.cta',
            'popover' => [
                'title'       => '&lt;script&gt;alert(1)&lt;/script&gt;',
                'description' => '&lt;img src=x onerror=&quot;alert(&#039;xss&#039;)&quot;&gt; l&#039;&quot;aide&quot;',
                'side'        => 'bottom',
                'align'       => 'start',
            ],
        ], $step->toArray());
    }

    #[Test]
    public function it_keeps_trusted_markup_untouched(): void
    {
        $step = new Step('.cta', new Markup('<b>Action</b>', 'UTF-8'));

        $this->assertSame('<b>Action</b>', $step->toArray()['popover']['title']);
    }

    #[Test]
    public function it_escapes_popover_content_added_through_the_builder(): void
    {
        $tour = (new Tour('onboarding'))
            ->addStep('.header', '<script>alert(1)</script>')
            ->addStep('.cta', new Markup('<b>Action</b>', 'UTF-8'));

        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $tour->getSteps()[0]['popover']['title']);
        $this->assertSame('<b>Action</b>', $tour->getSteps()[1]['popover']['title']);
    }

    #[Test]
    public function it_omits_null_descriptions_from_step_payload(): void
    {
        $step = new Step('.cta', 'Action');

        $this->assertSame([
            'element' => '.cta',
            'popover' => [
                'title' => 'Action',
                'side'  => 'bottom',
                'align' => 'start',
            ],
        ], $step->toArray());
    }
}
