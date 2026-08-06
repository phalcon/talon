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

namespace Phalcon\Talon\Tests\Unit\Database\Schema;

use PDO;
use Phalcon\Talon\Exceptions\SchemaConnectionMissing;
use Phalcon\Talon\Tests\Fixtures\Schema\WidgetSchema;
use PHPUnit\Framework\TestCase;

final class AbstractSchemaRuntimeTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testClearRemovesEveryRow(): void
    {
        $schema = $this->schema();
        $schema->create();
        $schema->insert(1, 'first');
        $schema->insert(2, 'second');

        $this->assertSame(2, $schema->clear());
        $this->assertSame(0, $this->rowCount());
    }

    public function testCreateRunsTheStatements(): void
    {
        $this->schema()->create();

        $this->assertSame(0, $this->rowCount());
    }

    public function testCreateWithoutConnectionThrows(): void
    {
        $schema = new WidgetSchema();

        $this->expectException(SchemaConnectionMissing::class);
        $this->expectExceptionMessage("Schema fixture for 'widgets' has no database connection");

        $schema->create();
    }

    public function testDropRemovesTheTable(): void
    {
        $schema = $this->schema();
        $schema->create();
        $schema->drop();

        $statement = $this->pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='widgets'"
        );
        $this->assertNotFalse($statement);

        $this->assertSame([], $statement->fetchAll());
    }

    public function testExecuteReturnsAffectedRows(): void
    {
        $schema = $this->schema();
        $schema->create();

        $this->assertSame(1, $schema->insert(1, 'first'));
    }

    public function testSetConnectionAttachesLater(): void
    {
        $schema = new WidgetSchema();
        $schema->setConnection($this->pdo);
        $schema->create();

        $this->assertSame(0, $this->rowCount());
    }

    private function rowCount(): int
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM widgets');
        $this->assertNotFalse($statement);

        return (int) $statement->fetchColumn();
    }

    private function schema(): WidgetSchema
    {
        return new WidgetSchema($this->pdo, false);
    }
}
