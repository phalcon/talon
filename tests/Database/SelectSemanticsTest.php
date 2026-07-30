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

namespace Phalcon\Talon\Tests\Database;

use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use PHPUnit\Framework\AssertionFailedError;

final class SelectSemanticsTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromEnv());
        self::resetConnections();

        $dialect = $this->getDialect();
        $table   = $dialect->quoteIdentifier('order');
        $key     = $dialect->quoteIdentifier('key');
        $note    = $dialect->quoteIdentifier('note');

        $this->getConnection()->execute('DROP TABLE IF EXISTS ' . $table);
        $this->getConnection()->execute(
            'CREATE TABLE ' . $table . ' (' . $key . ' INTEGER, ' . $note . ' VARCHAR(64))'
        );
        $this->getConnection()->execute('INSERT INTO ' . $table . ' VALUES (1, NULL)');
        $this->getConnection()->execute('INSERT INTO ' . $table . " VALUES (2, 'present')");
    }

    protected function tearDown(): void
    {
        $this->getConnection()->execute(
            'DROP TABLE IF EXISTS ' . $this->getDialect()->quoteIdentifier('order')
        );

        Talon::reset();

        parent::tearDown();
    }

    public function testEmptyCriteriaReturnsEveryRow(): void
    {
        $this->assertCount(2, $this->getFromDatabase('order'));
    }

    public function testMultipleCriteriaAreCombined(): void
    {
        $this->assertCount(1, $this->getFromDatabase('order', ['key' => 2, 'note' => 'present']));
        $this->assertCount(0, $this->getFromDatabase('order', ['key' => 1, 'note' => 'present']));
    }

    public function testNotInDatabaseWithNullIsNotVacuous(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->assertNotInDatabase('order', ['note' => null]);
    }

    public function testNullCriterionMatchesOnlyTheNullRow(): void
    {
        $rows = $this->getFromDatabase('order', ['note' => null]);

        $this->assertCount(1, $rows);
        // Loose comparison on purpose: pgsql and mysql return the integer as a
        // string, sqlite as an int, and Talon does not normalize result types.
        $this->assertEquals(1, $rows[0]['key']);
    }

    public function testReservedWordIdentifiersAreQuoted(): void
    {
        $this->assertInDatabase('order', ['key' => 2]);
    }
}
