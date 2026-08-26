<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Twig\Components;

use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\OverlayClickBehavior;
use Pentiminax\UX\Driver\Model\Tour as TourModel;
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
            'smoothScroll' => true,
            'overlayColor' => '#111111',
        ], $component->options());
    }

    /**
     * The two modes feed the same Stimulus value, so a divergence here means a tour behaves
     * differently depending on how it was declared.
     */
    #[Test]
    public function it_serializes_the_same_config_as_the_builder(): void
    {
        $component                           = new Tour();
        $component->id                       = 'onboarding';
        $component->animate                  = false;
        $component->duration                 = 150;
        $component->allowScroll              = true;
        $component->overlayClickBehavior     = 'nextStep';
        $component->stageRadius              = 12;
        $component->allowKeyboardControl     = false;
        $component->disableActiveInteraction = true;
        $component->popoverClass             = 'my-popover';
        $component->popoverOffset            = 16;
        $component->showButtons              = [Button::Next, 'previous'];
        $component->disableButtons           = ['close'];
        $component->progressText             = '{{current}} / {{total}}';
        $component->nextBtnText              = 'Suivant';
        $component->prevBtnText              = 'Précédent';
        $component->doneBtnText              = 'Terminer';

        $builder = (new TourModel('onboarding'))
            ->animate(false)
            ->duration(150)
            ->allowScroll()
            ->overlayClickBehavior(OverlayClickBehavior::NextStep)
            ->stageRadius(12)
            ->allowKeyboardControl(false)
            ->disableActiveInteraction()
            ->popoverClass('my-popover')
            ->popoverOffset(16)
            ->showButtons(Button::Next, 'previous')
            ->disableButtons('close')
            ->progressText('{{current}} / {{total}}')
            ->nextBtnText('Suivant')
            ->prevBtnText('Précédent')
            ->doneBtnText('Terminer');

        $this->assertSame($builder->getOptions(), $component->options());
    }

    /**
     * The defaults live in the model alone: a component nobody configured must send exactly
     * what an untouched builder sends, with no compensating call on either side.
     */
    #[Test]
    public function a_bare_component_serializes_like_a_bare_builder(): void
    {
        $component     = new Tour();
        $component->id = 'onboarding';

        $this->assertSame((new TourModel('onboarding'))->getOptions(), $component->options());
    }

    #[Test]
    public function it_rejects_an_unknown_button_name(): void
    {
        $component              = new Tour();
        $component->id          = 'onboarding';
        $component->showButtons = ['finish'];

        $this->expectException(\ValueError::class);

        $component->options();
    }

    #[Test]
    public function it_rejects_an_unknown_overlay_click_behavior(): void
    {
        $component                       = new Tour();
        $component->id                   = 'onboarding';
        $component->overlayClickBehavior = 'previousStep';

        $this->expectException(\ValueError::class);

        $component->options();
    }
}
