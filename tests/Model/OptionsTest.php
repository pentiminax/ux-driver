<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Model\Hint;
use Pentiminax\UX\Driver\Model\Options;
use Pentiminax\UX\Driver\Model\Step;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

/**
 * The one pass Step and Hint share. Both types are exercised here so a parsing fix can no
 * longer land on one side only.
 *
 * @internal
 */
#[CoversClass(Options::class)]
final class OptionsTest extends TestCase
{
    private const STEP_SCHEMA = [
        'popover' => ['popoverClass' => null, 'showButtons' => Options::BUTTONS],
        'step'    => ['waitForElement' => null],
    ];

    private const HINT_SCHEMA = [
        'popover' => ['popoverClass' => null],
        'hint'    => ['beacon' => Hint::BEACON_OPTIONS, 'data' => null],
    ];

    #[Test]
    public function it_buckets_and_normalizes_step_options(): void
    {
        $this->assertSame([
            'popover' => ['popoverClass' => 'my-popover', 'showButtons' => ['next', 'previous']],
            'step'    => ['waitForElement' => 500],
        ], Options::split([
            'popoverClass'   => 'my-popover',
            'showButtons'    => [Button::Next, 'previous'],
            'waitForElement' => 500,
        ], self::STEP_SCHEMA, 'step'));
    }

    #[Test]
    public function it_buckets_and_normalizes_hint_options(): void
    {
        $this->assertSame([
            'popover' => ['popoverClass' => 'my-popover'],
            'hint'    => [
                'beacon' => ['side' => 'left', 'align' => 'center', 'animate' => false],
                'data'   => ['plan' => 'pro'],
            ],
        ], Options::split([
            'popoverClass' => 'my-popover',
            'beacon'       => ['side' => Side::Left, 'align' => Align::Center, 'animate' => false],
            'data'         => ['plan' => 'pro'],
        ], self::HINT_SCHEMA, 'hint'));
    }

    #[Test]
    public function it_returns_every_declared_bucket_even_when_empty(): void
    {
        $this->assertSame(['popover' => [], 'step' => []], Options::split([], self::STEP_SCHEMA, 'step'));
    }

    /**
     * @param array<string, array<string, string|array<string, string|null>|null>> $schema
     */
    #[Test]
    #[DataProvider('schemas')]
    public function it_rejects_an_option_belonging_to_no_bucket(string $subject, array $schema, string $expected): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expected);

        Options::split(['onNextClick' => 'callback'], $schema, $subject);
    }

    /**
     * @return iterable<string, array{string, array<string, array<string, string|array<string, string|null>|null>>, string}>
     */
    public static function schemas(): iterable
    {
        yield 'step' => ['step', self::STEP_SCHEMA, 'Unknown step option "onNextClick". Serializable driver.js step options are: popoverClass, showButtons, waitForElement.'];
        yield 'hint' => ['hint', self::HINT_SCHEMA, 'Unknown hint option "onNextClick". Serializable driver.js hint options are: popoverClass, beacon, data.'];
    }

    #[Test]
    public function it_rejects_an_unknown_nested_option(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown beacon option "color". Serializable driver.js beacon options are: side, align, animate, className.');

        Options::split(['beacon' => ['color' => 'red']], self::HINT_SCHEMA, 'hint');
    }

    #[Test]
    public function it_rejects_a_nested_option_that_is_not_an_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "beacon" expects an array of driver.js beacon options, string given.');

        Options::split(['beacon' => 'left'], self::HINT_SCHEMA, 'hint');
    }

    #[Test]
    public function it_rejects_an_unknown_enum_value(): void
    {
        $this->expectException(\ValueError::class);

        Options::split(['beacon' => ['side' => 'upside-down']], self::HINT_SCHEMA, 'hint');
    }

    #[Test]
    public function it_escapes_the_popover_content_and_keeps_trusted_markup(): void
    {
        $this->assertSame([
            'title'        => '&lt;script&gt;alert(1)&lt;/script&gt;',
            'description'  => '<em>Trusted</em>',
            'side'         => 'left',
            'align'        => 'center',
            'popoverClass' => 'my-popover',
        ], Options::popover(
            '<script>alert(1)</script>',
            new Markup('<em>Trusted</em>', 'UTF-8'),
            'left',
            'center',
            ['popoverClass' => 'my-popover'],
        ));
    }

    #[Test]
    public function it_drops_the_popover_content_that_was_not_given(): void
    {
        $this->assertSame(['side' => 'bottom', 'align' => 'start'], Options::popover(null, null, 'bottom', 'start', []));
    }

    /**
     * The two models declare their own tables; this is what keeps them honest about using the
     * shared pass instead of a private copy.
     */
    #[Test]
    public function the_models_serialize_through_the_shared_pass(): void
    {
        $step = new Step('#save', 'Save', null, Side::Left, Align::Center, ['showButtons' => ['next']]);
        $hint = new Hint('#save', 'save', 'Save', null, Side::Left, Align::Center, ['beacon' => ['side' => Side::Top]]);

        $this->assertSame([
            'title'       => 'Save',
            'side'        => 'left',
            'align'       => 'center',
            'showButtons' => ['next'],
        ], $step->toArray()['popover']);

        $this->assertSame(['side' => 'top'], $hint->toArray()['beacon']);
    }
}
