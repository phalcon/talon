<?php

/**
 * This file is part of the Phalcon Talon.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Talon\Http;

use function array_key_exists;
use function explode;
use function get_debug_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;
use function str_contains;
use function strtotime;

/**
 * Validates a decoded JSON document against a map of type expectations.
 *
 * Keys absent from the type map are ignored, so a map can describe the part of
 * an envelope a test cares about while the document carries more.
 *
 * Supported specs: 'array', 'boolean', 'float', 'integer', 'null', 'string',
 * optionally suffixed with ':date', and joined with '|' to form a union.
 *
 * 'float' accepts an int as well as a float. JSON has a single number type, so
 * json_decode() hands back int(10) for {"price": 10} and float(10.5) for
 * {"price": 10.5} - the same field is one or the other depending on the value
 * it happens to carry. A strict 'float' would fail on every whole number.
 * 'integer' stays strict, so it still rejects 10.5.
 */
final class JsonType
{
    /**
     * @param array<array-key, mixed> $types
     *
     * @return string|null null when the document matches, otherwise the reason
     */
    public static function match(mixed $actual, array $types, string $path = ''): ?string
    {
        if (!is_array($actual)) {
            return sprintf("Key '%s' expected an object, got '%s'", $path, get_debug_type($actual));
        }

        foreach ($types as $key => $expected) {
            $current = self::path($path, $key);

            if (!array_key_exists($key, $actual)) {
                return sprintf("Key '%s' is missing", $current);
            }

            $error = self::matchValue($actual[$key], $expected, $current);
            if (null !== $error) {
                return $error;
            }
        }

        return null;
    }

    private static function matchesFilter(mixed $value, string $filter): bool
    {
        return match ($filter) {
            'date'  => is_string($value) && false !== strtotime($value),
            default => false,
        };
    }

    private static function matchesSingle(mixed $value, string $alternative): bool
    {
        $filter = null;
        if (str_contains($alternative, ':')) {
            [$alternative, $filter] = explode(':', $alternative, 2);
        }

        $matches = match ($alternative) {
            'array'   => is_array($value),
            'boolean' => is_bool($value),
            'float'   => is_float($value) || is_int($value),
            'integer' => is_int($value),
            'null'    => null === $value,
            'string'  => is_string($value),
            default   => false,
        };

        if (!$matches) {
            return false;
        }

        return null === $filter || self::matchesFilter($value, $filter);
    }

    private static function matchesSpec(mixed $value, string $spec): bool
    {
        foreach (explode('|', $spec) as $alternative) {
            if (self::matchesSingle($value, $alternative)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks one key's value against its spec: a nested map recurses, anything
     * else is a leaf. Keeping the two failure modes apart - the spec is not a
     * string, or the value does not satisfy it - is what lets each report
     * itself, rather than one branch re-deriving which case it caught.
     */
    private static function matchValue(mixed $value, mixed $expected, string $path): ?string
    {
        if (is_array($expected)) {
            return self::match($value, $expected, $path);
        }

        if (!is_string($expected)) {
            return self::typeError($path, get_debug_type($expected), $value);
        }

        if (!self::matchesSpec($value, $expected)) {
            return self::typeError($path, $expected, $value);
        }

        return null;
    }

    /**
     * @param array-key $key
     */
    private static function path(string $path, mixed $key): string
    {
        return '' === $path ? (string) $key : $path . '.' . $key;
    }

    private static function typeError(string $path, string $expected, mixed $value): string
    {
        return sprintf("Key '%s' expected '%s', got '%s'", $path, $expected, get_debug_type($value));
    }
}
