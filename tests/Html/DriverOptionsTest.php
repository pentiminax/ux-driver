<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Tests\Html;

use Pentiminax\UX\Driver\Html\DriverOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

/**
 * @internal
 */
#[CoversClass(DriverOptions::class)]
final class DriverOptionsTest extends TestCase
{
    #[Test]
    public function it_escapes_the_html_sinks_and_leaves_the_other_options_alone(): void
    {
        $escaped = DriverOptions::escape([
            'title'        => '<b>Export</b>',
            'description'  => null,
            'progressText' => '{{current}} <em>of</em> {{total}}',
            'nextBtnText'  => new Markup('<strong>Next</strong>', 'UTF-8'),
            'popoverClass' => '<not-a-sink>',
        ]);

        $this->assertSame([
            'title'        => '&lt;b&gt;Export&lt;/b&gt;',
            'description'  => null,
            'progressText' => '{{current}} &lt;em&gt;of&lt;/em&gt; {{total}}',
            'nextBtnText'  => '<strong>Next</strong>',
            'popoverClass' => '<not-a-sink>',
        ], $escaped);
    }

    /**
     * `string|Markup` is how the bundle marks a value that reaches innerHTML — Markup being the
     * opt-out. Any such field must be a known sink, otherwise it would be serialized raw.
     */
    #[Test]
    public function every_html_field_of_the_public_api_is_a_known_sink(): void
    {
        $markupFields = [];

        foreach ($this->publicApiClasses() as $class) {
            $reflection = new \ReflectionClass($class);

            foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if ($this->acceptsMarkup($property->getType())) {
                    $markupFields[$property->getName()] = true;
                }
            }

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                // A fluent option setter carries the field name itself; its parameter is just
                // the value ($text, $template).
                if (\in_array($method->getName(), DriverOptions::HTML_SINKS, true)) {
                    continue;
                }

                foreach ($method->getParameters() as $parameter) {
                    if ($this->acceptsMarkup($parameter->getType())) {
                        $markupFields[$parameter->getName()] = true;
                    }
                }
            }
        }

        $this->assertNotEmpty($markupFields);
        $this->assertSame([], array_diff(array_keys($markupFields), DriverOptions::HTML_SINKS));
    }

    /**
     * @return list<class-string>
     */
    private function publicApiClasses(): array
    {
        $src     = \dirname(__DIR__, 2).'/src';
        $classes = [];

        /** @var iterable<\SplFileInfo> $files */
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }

            $relative = substr($file->getPathname(), \strlen($src) + 1);

            // src/Html is the escaping implementation itself: its parameters are values, not
            // driver.js options.
            if (str_starts_with($relative, 'Html'.\DIRECTORY_SEPARATOR)) {
                continue;
            }

            /** @var class-string $class */
            $class = 'Pentiminax\UX\Driver\\'.str_replace([\DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    private function acceptsMarkup(?\ReflectionType $type): bool
    {
        if (!$type instanceof \ReflectionUnionType) {
            return false;
        }

        foreach ($type->getTypes() as $member) {
            if ($member instanceof \ReflectionNamedType && Markup::class === $member->getName()) {
                return true;
            }
        }

        return false;
    }
}
