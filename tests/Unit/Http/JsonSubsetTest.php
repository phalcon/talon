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

use Phalcon\Talon\Http\JsonSubset;
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;

final class JsonSubsetTest extends AbstractUnitTestCase
{
    public function testEmptyExpectedListAgainstEmptyActualList(): void
    {
        $this->assertTrue(JsonSubset::contains(['data' => []], ['data' => []]));
    }

    public function testEmptyExpectedMatchesAnything(): void
    {
        $this->assertTrue(JsonSubset::contains(['a' => 1], []));
    }

    public function testExpectedArrayAgainstScalarActualFails(): void
    {
        $this->assertFalse(JsonSubset::contains(['a' => 'scalar'], ['a' => ['b' => 1]]));
    }

    public function testExpectedListAgainstEmptyActualListFails(): void
    {
        $this->assertFalse(JsonSubset::contains(['data' => []], ['data' => [['id' => 1]]]));
    }

    public function testListElementMatchesOutOfOrder(): void
    {
        $actual = ['data' => [['id' => 1], ['id' => 2], ['id' => 3]]];

        $this->assertTrue(JsonSubset::contains($actual, ['data' => [['id' => 3], ['id' => 1]]]));
    }

    public function testListElementNotPresentFails(): void
    {
        $actual = ['data' => [['id' => 1]]];

        $this->assertFalse(JsonSubset::contains($actual, ['data' => [['id' => 9]]]));
    }

    public function testMatchesFragmentOfLargerDocument(): void
    {
        $actual = [
            'jsonapi' => ['version' => '1.0'],
            'data'    => [['id' => 1, 'name' => 'Acme']],
            'meta'    => ['timestamp' => '2026-07-15T00:00:00+00:00'],
        ];

        $this->assertTrue(JsonSubset::contains($actual, ['data' => [['name' => 'Acme']]]));
        $this->assertTrue(JsonSubset::contains($actual, ['jsonapi' => ['version' => '1.0']]));
    }

    public function testMismatchedScalarFails(): void
    {
        $this->assertFalse(JsonSubset::contains(['a' => 1], ['a' => 2]));
    }

    public function testMissingKeyFails(): void
    {
        $this->assertFalse(JsonSubset::contains(['a' => 1], ['b' => 1]));
    }

    public function testNestedListInsideMapInsideList(): void
    {
        $actual = [
            'data' => [
                ['id' => 1, 'tags' => ['a', 'b']],
                ['id' => 2, 'tags' => ['c']],
            ],
        ];

        $this->assertTrue(JsonSubset::contains($actual, ['data' => [['tags' => ['b']]]]));
        $this->assertFalse(JsonSubset::contains($actual, ['data' => [['tags' => ['z']]]]));
    }

    public function testNullExpectedMatchesNullActual(): void
    {
        $this->assertTrue(JsonSubset::contains(['a' => null], ['a' => null]));
        $this->assertFalse(JsonSubset::contains(['a' => 0], ['a' => null]));
    }

    public function testScalarComparisonIsStrict(): void
    {
        $this->assertFalse(JsonSubset::contains(['a' => 1], ['a' => '1']));
    }

    public function testTopLevelScalarComparison(): void
    {
        $this->assertTrue(JsonSubset::contains('same', 'same'));
        $this->assertFalse(JsonSubset::contains('one', 'other'));
    }
}
