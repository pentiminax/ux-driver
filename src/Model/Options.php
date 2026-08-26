<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

use Pentiminax\UX\Driver\Enum\Align;
use Pentiminax\UX\Driver\Enum\Button;
use Pentiminax\UX\Driver\Enum\Side;
use Pentiminax\UX\Driver\Html\DriverOptions;
use Twig\Markup;

/**
 * The option pass Step and Hint share: validate the names against the type's own tables,
 * normalize the values carrying an enum, and hand back one array per driver.js bucket.
 *
 * Step and Hint remain two distinct interfaces; only this pass is common, and each type keeps
 * declaring its own tables. Adding an option is one entry there, never a new code branch.
 */
final class Options
{
    /** Normalizers a table entry may ask for. A null entry passes the value through. */
    public const BUTTONS = 'buttons';

    public const SIDE = 'side';

    public const ALIGN = 'align';

    /**
     * @param array<string, mixed>                                                 $options
     * @param array<string, array<string, string|array<string, string|null>|null>> $schema  bucket name => option name => normalizer, or a nested table for an option holding its own options
     * @param string                                                               $subject the option kind named by the error message
     *
     * @return array<string, array<string, mixed>> one entry per bucket, in the schema order
     *
     * @throws \InvalidArgumentException when an option belongs to no bucket
     * @throws \ValueError               when a button, side or alignment value is unknown
     */
    public static function split(array $options, array $schema, string $subject): array
    {
        $buckets = array_fill_keys(array_keys($schema), []);

        foreach ($options as $key => $value) {
            $bucket = self::bucketOf($key, $schema);

            if (null === $bucket) {
                throw new \InvalidArgumentException(\sprintf('Unknown %s option "%s". Serializable driver.js %s options are: %s.', $subject, $key, $subject, implode(', ', self::names($schema))));
            }

            $normalizer = $schema[$bucket][$key];

            if (!\is_array($normalizer)) {
                $buckets[$bucket][$key] = self::normalize($normalizer, $value);

                continue;
            }

            if (!\is_array($value)) {
                throw new \InvalidArgumentException(\sprintf('Option "%s" expects an array of driver.js %s options, %s given.', $key, $key, get_debug_type($value)));
            }

            /* @var array<string, mixed> $value */
            $buckets[$bucket][$key] = self::normalizeAll($value, $normalizer, $key);
        }

        return $buckets;
    }

    /**
     * The flat variant: a single table, no bucketing.
     *
     * @param array<string, mixed>       $options
     * @param array<string, string|null> $table
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when an option is not in the table
     * @throws \ValueError               when a button, side or alignment value is unknown
     */
    public static function normalizeAll(array $options, array $table, string $subject): array
    {
        $normalized = [];

        foreach ($options as $key => $value) {
            if (!\array_key_exists($key, $table)) {
                throw new \InvalidArgumentException(\sprintf('Unknown %s option "%s". Serializable driver.js %s options are: %s.', $subject, $key, $subject, implode(', ', array_keys($table))));
            }

            $normalized[$key] = self::normalize($table[$key], $value);
        }

        return $normalized;
    }

    /**
     * The popover both types nest in their payload: the escaped content, the anchor, then the
     * options bucketed under `popover`.
     *
     * @param array<string, mixed> $popoverOptions
     *
     * @return array<string, mixed>
     */
    public static function popover(
        string|Markup|null $title,
        string|Markup|null $description,
        string $side,
        string $align,
        array $popoverOptions,
    ): array {
        return array_filter(DriverOptions::escape([
            'title'       => $title,
            'description' => $description,
        ]) + [
            'side'  => $side,
            'align' => $align,
        ], static fn ($value) => null !== $value) + $popoverOptions;
    }

    /**
     * @param array<string, array<string, string|array<string, string|null>|null>> $schema
     */
    private static function bucketOf(string $key, array $schema): ?string
    {
        foreach ($schema as $bucket => $table) {
            if (\array_key_exists($key, $table)) {
                return $bucket;
            }
        }

        return null;
    }

    /**
     * @param array<string, array<string, string|array<string, string|null>|null>> $schema
     *
     * @return list<string>
     */
    private static function names(array $schema): array
    {
        return array_merge(...array_map(array_keys(...), array_values($schema)));
    }

    private static function normalize(?string $normalizer, mixed $value): mixed
    {
        if (self::BUTTONS === $normalizer) {
            /** @var iterable<Button|string> $buttons */
            $buttons = is_iterable($value) ? $value : [$value];

            return Button::normalizeAll($buttons);
        }

        if (self::SIDE === $normalizer) {
            \assert(\is_string($value) || $value instanceof Side);

            return Side::normalize($value);
        }

        if (self::ALIGN === $normalizer) {
            \assert(\is_string($value) || $value instanceof Align);

            return Align::normalize($value);
        }

        return $value;
    }
}
