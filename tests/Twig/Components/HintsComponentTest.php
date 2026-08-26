<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Twig\Components;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Model\Hints as HintsModel;
use Pentiminax\UX\Driver\Twig\Components\Hints;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Hints::class)]
final class HintsComponentTest extends TestCase
{
    #[Test]
    public function a_bare_component_serializes_like_a_bare_builder(): void
    {
        $component     = new Hints();
        $component->id = 'help';

        $this->assertSame((new HintsModel('help'))->getOptions(), $component->options());
    }

    /**
     * The two modes feed the same Stimulus value, so a divergence here means a hint group
     * behaves differently depending on how it was declared.
     */
    #[Test]
    public function it_serializes_the_same_config_as_the_builder(): void
    {
        $component                 = new Hints();
        $component->id             = 'help';
        $component->beacon         = ['side' => 'top', 'align' => 'center', 'animate' => false];
        $component->buttonText     = 'Compris';
        $component->popoverClass   = 'help-popover';
        $component->popoverOffset  = 16;
        $component->overlay        = true;
        $component->overlayColor   = '#0f172a';
        $component->overlayOpacity = 0.5;

        $builder = (new HintsModel('help'))
            ->beacon(side: Side::Top, align: Align::Center, animate: false)
            ->buttonText('Compris')
            ->popoverClass('help-popover')
            ->popoverOffset(16)
            ->overlay()
            ->overlayColor('#0f172a')
            ->overlayOpacity(0.5);

        $this->assertSame($builder->getOptions(), $component->options());
    }

    #[Test]
    public function it_rejects_an_unknown_beacon_option(): void
    {
        $component         = new Hints();
        $component->id     = 'help';
        $component->beacon = ['color' => 'red'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown beacon option "color"');

        $component->options();
    }

    #[Test]
    public function it_rejects_an_unknown_beacon_side(): void
    {
        $component         = new Hints();
        $component->id     = 'help';
        $component->beacon = ['side' => 'upside-down'];

        $this->expectException(\ValueError::class);

        $component->options();
    }
}
