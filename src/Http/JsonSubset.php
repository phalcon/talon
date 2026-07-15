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

use function array_is_list;
use function array_key_exists;
use function is_array;

/**
 * Recursive subset matching for JSON documents.
 *
 * Keys and list elements present in the actual document but absent from the
 * expected one are ignored, so a fragment can be asserted against a full
 * envelope. List elements match in any order.
 *
 * The one place this stops being a subset is the empty list: ['data' => []]
 * requires data to be empty rather than matching any data at all. Read as a
 * pure subset it would match everything, which is never what the caller meant
 * and asserts nothing.
 */
final class JsonSubset
{
    public static function contains(mixed $actual, mixed $expected): bool
    {
        if (!is_array($expected)) {
            return $actual === $expected;
        }

        if (!is_array($actual)) {
            return false;
        }

        if (array_is_list($expected)) {
            return self::containsList($actual, $expected);
        }

        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual) || !self::contains($actual[$key], $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<array-key, mixed> $actual
     * @param array<int, mixed>       $expected
     */
    private static function containsList(array $actual, array $expected): bool
    {
        if ([] === $expected) {
            return [] === $actual;
        }

        foreach ($expected as $item) {
            $found = false;
            foreach ($actual as $candidate) {
                if (self::contains($candidate, $item)) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }

        return true;
    }
}
