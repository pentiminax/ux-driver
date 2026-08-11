<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Model;

use Pentiminax\UX\Driver\Model\Step;
use Pentiminax\UX\Driver\Model\Tour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
