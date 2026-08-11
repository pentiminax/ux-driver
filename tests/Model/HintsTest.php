<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Model;

use Pentiminax\UX\Driver\Model\Hint;
use Pentiminax\UX\Driver\Model\Hints;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

/**
 * @internal
 */
#[CoversClass(Hint::class)]
#[CoversClass(Hints::class)]
final class HintsTest extends TestCase
{
    #[Test]
    public function it_builds_hints_and_options_fluently(): void
    {
        $hints = (new Hints('help'))
            ->addHint('.export', 'export', 'Exporter', 'Téléchargez vos données')
            ->addHint('.filters', 'filters', 'Filtres', side: 'right')
            ->beacon(side: 'top', align: 'end', animate: false, className: 'my-beacon')
            ->buttonText('Compris')
            ->popoverClass('help-popover')
            ->popoverOffset(12)
            ->overlay()
            ->overlayColor('#0f172a')
            ->overlayOpacity(0.4);

        $this->assertSame('help', $hints->id);
        $this->assertSame([
            [
                'element' => '.export',
                'id'      => 'export',
                'popover' => [
                    'title'       => 'Exporter',
                    'description' => 'Téléchargez vos données',
                    'side'        => 'bottom',
                    'align'       => 'start',
                ],
            ],
            [
                'element' => '.filters',
                'id'      => 'filters',
                'popover' => [
                    'title' => 'Filtres',
                    'side'  => 'right',
                    'align' => 'start',
                ],
            ],
        ], $hints->getHints());
        $this->assertSame([
            'beacon'         => ['side' => 'top', 'align' => 'end', 'animate' => false, 'className' => 'my-beacon'],
            'buttonText'     => 'Compris',
            'popoverClass'   => 'help-popover',
            'popoverOffset'  => 12,
            'overlay'        => true,
            'overlayColor'   => '#0f172a',
            'overlayOpacity' => 0.4,
        ], $hints->getOptions());
    }

    #[Test]
    public function it_serializes_every_hint_option(): void
    {
        $hint = new Hint('.export', 'export', 'Exporter', options: [
            'popoverClass' => 'help-popover',
            'showButton'   => true,
            'buttonText'   => 'Compris',
            'beacon'       => ['side' => 'top', 'align' => 'center', 'animate' => false, 'className' => 'dot'],
            'data'         => ['tracking' => 'export'],
        ]);

        $this->assertSame([
            'element' => '.export',
            'id'      => 'export',
            'beacon'  => ['side' => 'top', 'align' => 'center', 'animate' => false, 'className' => 'dot'],
            'popover' => [
                'title'        => 'Exporter',
                'side'         => 'bottom',
                'align'        => 'start',
                'popoverClass' => 'help-popover',
                'showButton'   => true,
                'buttonText'   => 'Compris',
            ],
            'data' => ['tracking' => 'export'],
        ], $hint->toArray());
    }

    /**
     * The declarative mode already carries element, id, title, description, side and align as
     * their own attributes; only what is left travels as JSON.
     */
    #[Test]
    public function it_exposes_the_remaining_options_as_extras(): void
    {
        $hint = new Hint('.export', options: [
            'popoverClass' => 'help-popover',
            'beacon'       => ['side' => 'top'],
            'data'         => ['tracking' => 'export'],
        ]);

        $this->assertSame([
            'beacon'  => ['side' => 'top'],
            'popover' => ['popoverClass' => 'help-popover'],
            'data'    => ['tracking' => 'export'],
        ], $hint->extras());
        $this->assertSame([], (new Hint('.export', title: 'Exporter'))->extras());
    }

    /**
     * driver.js writes the hint title, description and button label with innerHTML, exactly
     * like the tour popover content.
     */
    #[Test]
    public function it_escapes_the_hint_content(): void
    {
        $hint = new Hint(
            '.export',
            title: '<script>alert(1)</script>',
            description: new Markup('<b>Export</b>', 'UTF-8'),
            options: ['buttonText' => '<img src=x onerror=alert(1)>'],
        );

        $popover = $hint->toArray()['popover'];

        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $popover['title']);
        $this->assertSame('<b>Export</b>', $popover['description']);
        $this->assertSame('&lt;img src=x onerror=alert(1)&gt;', $popover['buttonText']);
    }

    #[Test]
    public function it_rejects_an_unknown_hint_option(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown hint option "onOpen"');

        new Hint('.export', options: ['onOpen' => 'noop']);
    }

    #[Test]
    public function it_rejects_an_unknown_beacon_option(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown beacon option "color"');

        new Hint('.export', options: ['beacon' => ['color' => 'red']]);
    }

    #[Test]
    public function it_rejects_an_unknown_beacon_side(): void
    {
        $this->expectException(\ValueError::class);

        new Hint('.export', options: ['beacon' => ['side' => 'diagonal']]);
    }

    #[Test]
    public function it_omits_the_id_when_none_is_given(): void
    {
        $hint = new Hint('.export', title: 'Exporter');

        $this->assertNull($hint->getId());
        $this->assertArrayNotHasKey('id', $hint->toArray());
    }
}
