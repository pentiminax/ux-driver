<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PackageMetadataTest extends TestCase
{
    private const DRIVER_JS_RANGE = '^1.8.0';

    #[Test]
    public function driver_js_range_is_the_documented_baseline_everywhere(): void
    {
        $contents = file_get_contents(\dirname(__DIR__).'/assets/package.json');

        self::assertIsString($contents);

        $package = json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($package);
        self::assertIsArray($package['peerDependencies'] ?? null);
        self::assertIsArray($package['symfony']['importmap'] ?? null);

        $peer      = $package['peerDependencies']['driver.js']     ?? null;
        $importmap = $package['symfony']['importmap']['driver.js'] ?? null;

        self::assertSame($peer, $importmap, 'peerDependencies and symfony.importmap must declare the same driver.js range.');
        self::assertSame(self::DRIVER_JS_RANGE, $peer);
    }
}
