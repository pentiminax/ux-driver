<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Twig\Components;

use Pentiminax\UX\Driver\Twig\Components\Tour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Tour::class)]
final class TourComponentTest extends TestCase
{
    #[Test]
    public function it_exposes_filtered_options_for_the_template(): void
    {
        $component                 = new Tour();
        $component->id             = 'onboarding';
        $component->showProgress   = true;
        $component->smoothScroll   = true;
        $component->overlayColor   = '#111111';
        $component->overlayOpacity = null;

        $this->assertSame([
            'showProgress' => true,
            'animate'      => true,
            'smoothScroll' => true,
            'allowClose'   => true,
            'overlayColor' => '#111111',
        ], $component->options());
    }
}
