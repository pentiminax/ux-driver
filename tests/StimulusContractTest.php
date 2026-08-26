<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests;

use Pentiminax\UX\Driver\Builder\HintsBuilder;
use Pentiminax\UX\Driver\Builder\TourBuilder;
use Pentiminax\UX\Driver\Model\Hints;
use Pentiminax\UX\Driver\Model\Tour;
use Pentiminax\UX\Driver\StimulusContract;
use Pentiminax\UX\Driver\Twig\UXDriverExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The PHP↔JS seam, asserted from both sides.
 *
 * StimulusContract is what the templates and UXDriverExtension render; the Stimulus
 * controllers are the other half, and nothing else compares the two. This test reads the
 * TypeScript sources — the way AssetsDistTest already reads assets/ from PHP — so renaming a
 * value, an action or a `data-*` attribute on one side only fails here instead of becoming a
 * silent runtime no-op.
 *
 * @internal
 */
#[CoversClass(StimulusContract::class)]
final class StimulusContractTest extends TestCase
{
    /** The controller each identifier is declared in. */
    private const CONTROLLER_SOURCES = [
        StimulusContract::TOUR  => 'controller.ts',
        StimulusContract::HINTS => 'hints-controller.ts',
    ];

    /** Where each controller reads its target's `data-*` attributes back through `dataset`. */
    private const DATASET_SOURCES = [
        StimulusContract::TOUR  => 'tour-utils.ts',
        StimulusContract::HINTS => 'hint-utils.ts',
    ];

    /** Stimulus lifecycle callbacks: methods, but not actions. */
    private const LIFECYCLE = ['initialize', 'connect', 'disconnect'];

    /**
     * @return iterable<string, array{string}>
     */
    public static function identifiers(): iterable
    {
        foreach (array_keys(self::CONTROLLER_SOURCES) as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('identifiers')]
    public function the_declared_values_are_the_controller_values(string $identifier): void
    {
        $block = $this->block($identifier, 'values', '{', '}');

        self::assertSame(
            StimulusContract::VALUES[$identifier],
            $this->captured('/^ {8}(\w+):/m', $block),
            \sprintf('StimulusContract::VALUES must list the `static values` of assets/src/%s.', self::CONTROLLER_SOURCES[$identifier]),
        );
    }

    #[Test]
    #[DataProvider('identifiers')]
    public function the_declared_target_is_the_controller_target(string $identifier): void
    {
        self::assertSame(
            [StimulusContract::TARGETS[$identifier]],
            $this->captured("/'(\w+)'/", $this->block($identifier, 'targets', '[', ']')),
            \sprintf('StimulusContract::TARGETS must list the `static targets` of assets/src/%s.', self::CONTROLLER_SOURCES[$identifier]),
        );
    }

    /**
     * Every public method that is not a lifecycle callback is reachable through
     * `ux_tour_action()` / `ux_hints_action()`, and every action must reach a method.
     */
    #[Test]
    #[DataProvider('identifiers')]
    public function the_declared_actions_are_the_controller_methods(string $identifier): void
    {
        $methods = array_values(array_diff(
            $this->captured('/^ {4}([a-zA-Z_]\w*)\(/m', $this->source(self::CONTROLLER_SOURCES[$identifier])),
            self::LIFECYCLE,
        ));

        self::assertSame(
            $this->sorted(StimulusContract::ACTIONS[$identifier]),
            $this->sorted($methods),
            \sprintf('StimulusContract::ACTIONS must list the action methods of assets/src/%s.', self::CONTROLLER_SOURCES[$identifier]),
        );
    }

    #[Test]
    #[DataProvider('identifiers')]
    public function the_declared_attributes_are_the_attributes_read_from_the_dataset(string $identifier): void
    {
        $prefix = StimulusContract::TARGETS[$identifier];
        $read   = [];

        foreach ($this->captured('/dataset\.(\w+)/', $this->source(self::DATASET_SOURCES[$identifier])) as $key) {
            self::assertStringStartsWith($prefix, $key, \sprintf('`dataset.%s` is not a "%s" target attribute.', $key, $prefix));
            $read[] = lcfirst(substr($key, \strlen($prefix)));
        }

        self::assertSame(
            $this->sorted(StimulusContract::ATTRIBUTES[$identifier]),
            $this->sorted($read),
            \sprintf('StimulusContract::ATTRIBUTES must list the data-%s-* attributes assets/src/%s reads.', $prefix, self::DATASET_SOURCES[$identifier]),
        );
    }

    /**
     * The builder mode does not go through the components: it hands its values straight to
     * StimulusHelper, so its keys are a copy of the contract too.
     */
    #[Test]
    public function the_builder_mode_renders_only_declared_values(): void
    {
        $extension = new UXDriverExtension(
            new StimulusHelper(new Environment(new ArrayLoader())),
            new TourBuilder(),
            new HintsBuilder(),
        );

        self::assertSame(
            $this->sorted(StimulusContract::VALUES[StimulusContract::TOUR]),
            $this->renderedValues(StimulusContract::TOUR, (string) $extension->renderTour(new Tour('onboarding'))),
        );
        self::assertSame(
            $this->sorted(StimulusContract::VALUES[StimulusContract::TOUR]),
            $this->renderedValues(StimulusContract::TOUR, (string) $extension->renderHighlight('.help', 'Aide')),
        );
        self::assertSame(
            $this->sorted(StimulusContract::VALUES[StimulusContract::HINTS]),
            $this->renderedValues(StimulusContract::HINTS, (string) $extension->renderHints(new Hints('help'))),
        );
    }

    /**
     * The two spellings of the same controller: StimulusHelper takes the package-relative name
     * and dasherizes it into the identifier the templates and the DOM use.
     */
    #[Test]
    #[DataProvider('identifiers')]
    public function the_controller_name_matches_the_identifier(string $identifier): void
    {
        $name = StimulusContract::CONTROLLERS[$identifier];

        self::assertSame($identifier, str_replace(['@', '/'], ['', '--'], $name));

        $package = json_decode($this->contents(\dirname(__DIR__).'/assets/package.json'), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($package);
        self::assertArrayHasKey(
            substr($name, (int) strrpos($name, '/') + 1),
            $package['symfony']['controllers'] ?? [],
            \sprintf('"%s" must be declared in the symfony.controllers of assets/package.json.', $name),
        );
    }

    /**
     * @return list<string>
     */
    private function renderedValues(string $identifier, string $markup): array
    {
        return $this->sorted($this->captured('/data-'.preg_quote($identifier, '/').'-([\w-]+?)-value=/', $markup));
    }

    /**
     * The body of a `static <name> = …` declaration.
     */
    private function block(string $identifier, string $name, string $open, string $close): string
    {
        $source  = $this->source(self::CONTROLLER_SOURCES[$identifier]);
        $pattern = \sprintf('/static %s = %s(.*?)%s;/s', $name, preg_quote($open, '/'), preg_quote($close, '/'));

        if (1 !== preg_match($pattern, $source, $matches)) {
            self::fail(\sprintf('No `static %s` declaration in assets/src/%s.', $name, self::CONTROLLER_SOURCES[$identifier]));
        }

        return $matches[1];
    }

    /**
     * @return list<string> the first capturing group of every match, deduplicated
     */
    private function captured(string $pattern, string $subject): array
    {
        preg_match_all($pattern, $subject, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param list<string> $names
     *
     * @return list<string>
     */
    private function sorted(array $names): array
    {
        sort($names);

        return $names;
    }

    private function source(string $file): string
    {
        return $this->contents(\dirname(__DIR__).'/assets/src/'.$file);
    }

    private function contents(string $path): string
    {
        $contents = file_get_contents($path);

        self::assertIsString($contents, \sprintf('Unable to read %s.', $path));

        return $contents;
    }
}
