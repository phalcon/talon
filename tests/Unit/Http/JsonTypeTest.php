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

namespace Phalcon\Talon\Tests\Unit\Http;

use Phalcon\Talon\Http\JsonType;
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;

final class JsonTypeTest extends AbstractUnitTestCase
{
    public function testDateFilterAcceptsIsoDate(): void
    {
        $this->assertNull(JsonType::match(['t' => '2026-07-15T10:30:00+00:00'], ['t' => 'string:date']));
    }

    public function testDateFilterRejectsNonDateString(): void
    {
        $this->assertSame(
            "Key 't' expected 'string:date', got 'string'",
            JsonType::match(['t' => 'not a date'], ['t' => 'string:date'])
        );
    }

    public function testEmptyTypeMapMatchesAnything(): void
    {
        $this->assertNull(JsonType::match(['a' => 1], []));
    }

    /**
     * JSON has one number type: {"price": 10} decodes to int and
     * {"price": 10.5} to float, so 'float' has to accept both or it fails on
     * every whole number. 'integer' stays strict.
     */
    public function testFloatAcceptsWholeNumbersButIntegerStaysStrict(): void
    {
        $this->assertNull(JsonType::match(['a' => 1], ['a' => 'float']));
        $this->assertNull(JsonType::match(['a' => 1.5], ['a' => 'float']));
        $this->assertNotNull(JsonType::match(['a' => 1.5], ['a' => 'integer']));
        $this->assertNull(JsonType::match(['a' => 1], ['a' => 'integer']));
    }

    /**
     * A decoded JSON array is int-keyed, so the path built for a nested map at
     * an integer key must still be a string.
     */
    public function testIntegerKeyedNestedMapReportsAStringPath(): void
    {
        $this->assertSame(
            "Key '0.a' expected 'string', got 'int'",
            JsonType::match([['a' => 1]], [0 => ['a' => 'string']])
        );
    }

    /**
     * A nested map that matches must not end the walk - the keys after it still
     * have to be checked.
     */
    public function testKeyAfterAMatchingNestedMapIsStillChecked(): void
    {
        $this->assertSame(
            "Key 'meta.hash' expected 'string', got 'int'",
            JsonType::match(
                [
                    'jsonapi' => ['version' => '1.0'],
                    'meta'    => ['hash' => 1],
                ],
                [
                    'jsonapi' => ['version' => 'string'],
                    'meta'    => ['hash' => 'string'],
                ]
            )
        );
    }

    public function testMatchesTheRestApiEnvelope(): void
    {
        $actual = [
            'jsonapi' => ['version' => '1.0'],
            'data'    => [['id' => 1]],
            'meta'    => [
                'timestamp' => '2026-07-15T10:30:00+00:00',
                'hash'      => 'a1b2c3',
            ],
        ];

        $result = JsonType::match(
            $actual,
            [
                'jsonapi' => ['version' => 'string'],
                'meta'    => [
                    'timestamp' => 'string:date',
                    'hash'      => 'string',
                ],
            ]
        );

        $this->assertNull($result);
    }

    public function testMissingKeyFails(): void
    {
        $this->assertSame("Key 'b' is missing", JsonType::match(['a' => 1], ['b' => 'integer']));
    }

    public function testMissingNestedKeyReportsPath(): void
    {
        $this->assertSame(
            "Key 'meta.hash' is missing",
            JsonType::match(['meta' => ['timestamp' => 'x']], ['meta' => ['hash' => 'string']])
        );
    }

    public function testNestedMapAgainstNonArrayFails(): void
    {
        $this->assertSame(
            "Key 'meta' expected an object, got 'string'",
            JsonType::match(['meta' => 'scalar'], ['meta' => ['hash' => 'string']])
        );
    }

    public function testNonStringNonArraySpecFails(): void
    {
        $this->assertSame(
            "Key 'a' expected 'int', got 'int'",
            JsonType::match(['a' => 1], ['a' => 123])
        );
    }

    public function testScalarTypes(): void
    {
        $this->assertNull(JsonType::match(['a' => true], ['a' => 'boolean']));
        $this->assertNull(JsonType::match(['a' => [1, 2]], ['a' => 'array']));
        $this->assertNull(JsonType::match(['a' => null], ['a' => 'null']));
        $this->assertNull(JsonType::match(['a' => 'x'], ['a' => 'string']));
        $this->assertNull(JsonType::match(['a' => 1], ['a' => 'integer']));
    }

    public function testTopLevelNonArrayFails(): void
    {
        $this->assertSame(
            "Key '' expected an object, got 'string'",
            JsonType::match('scalar', ['a' => 'string'])
        );
    }

    public function testUnionAcceptsEitherAlternative(): void
    {
        $this->assertNull(JsonType::match(['a' => null], ['a' => 'string|null']));
        $this->assertNull(JsonType::match(['a' => 'x'], ['a' => 'string|null']));
        $this->assertNotNull(JsonType::match(['a' => 1], ['a' => 'string|null']));
    }

    public function testUnknownBaseTypeNeverMatches(): void
    {
        $this->assertSame(
            "Key 'a' expected 'bogus', got 'int'",
            JsonType::match(['a' => 1], ['a' => 'bogus'])
        );
    }

    public function testUnknownFilterNeverMatches(): void
    {
        $this->assertSame(
            "Key 't' expected 'string:bogus', got 'string'",
            JsonType::match(['t' => 'anything'], ['t' => 'string:bogus'])
        );
    }

    public function testUnlistedKeysAreIgnored(): void
    {
        $this->assertNull(JsonType::match(['a' => 1, 'b' => 'x'], ['a' => 'integer']));
    }

    public function testWrongTypeReportsPath(): void
    {
        $this->assertSame(
            "Key 'meta.hash' expected 'string', got 'int'",
            JsonType::match(['meta' => ['hash' => 1]], ['meta' => ['hash' => 'string']])
        );
    }
}
