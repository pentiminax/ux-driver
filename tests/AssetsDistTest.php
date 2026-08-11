<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A CSS import left in the built controller breaks native AssetMapper/importmap
 * loading (the browser refuses to execute CSS as a JS module). The stylesheet
 * must travel through the StimulusBundle "autoimport" declaration instead, which
 * itself requires a matching "symfony.importmap" entry (see StimulusBundle's
 * AutoImportLocator::locateAutoImport()).
 *
 * @internal
 */
final class AssetsDistTest extends TestCase
{
    private const DRIVER_CSS = 'driver.js/dist/driver.css';

    #[Test]
    public function built_controller_has_no_css_import(): void
    {
        $contents = file_get_contents(\dirname(__DIR__).'/assets/dist/controller.js');

        self::assertIsString($contents);
        self::assertDoesNotMatchRegularExpression(
            '/^\s*import\s+[^;\n]*\.css[\'"];?\s*$/m',
            $contents,
            'assets/dist/controller.js must not import a CSS file at runtime.',
        );
    }

    #[Test]
    public function driver_css_is_declared_as_autoimport_and_in_the_importmap(): void
    {
        $contents = file_get_contents(\dirname(__DIR__).'/assets/package.json');

        self::assertIsString($contents);

        $package = json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($package);
        self::assertArrayHasKey(
            self::DRIVER_CSS,
            $package['symfony']['controllers']['tour']['autoimport'] ?? [],
            'The driver.js stylesheet must be declared as a Stimulus autoimport.',
        );
        self::assertArrayHasKey(
            self::DRIVER_CSS,
            $package['symfony']['importmap'] ?? [],
            'The autoimport is resolved through importmap.php, so it needs a symfony.importmap entry too.',
        );
    }
}
