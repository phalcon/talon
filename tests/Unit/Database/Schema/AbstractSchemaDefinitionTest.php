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

use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\Database\Schema\AbstractSchema;
use Phalcon\Talon\Database\Schema\SchemaDefinition;
use Phalcon\Talon\Database\Schema\SchemaFixture;
use PHPUnit\Framework\TestCase;

final class AbstractSchemaDefinitionTest extends TestCase
{
    public function testGetDependenciesDefaultsToEmpty(): void
    {
        $this->assertSame([], $this->schema()->getDependencies());
    }

    public function testGetStatementsDispatchesPerDialect(): void
    {
        $schema = $this->schema();

        $this->assertSame(['mysql'], $schema->getStatements(Dialect::Mysql));
        $this->assertSame(['pgsql'], $schema->getStatements(Dialect::Pgsql));
        $this->assertSame(['sqlite'], $schema->getStatements(Dialect::Sqlite));
    }

    public function testGetTableReturnsTheDeclaredName(): void
    {
        $this->assertSame('widgets', $this->schema()->getTable());
    }

    public function testImplementsBothContracts(): void
    {
        $schema = $this->schema();

        $this->assertInstanceOf(SchemaDefinition::class, $schema);
        $this->assertInstanceOf(SchemaFixture::class, $schema);
    }

    public function testInstantiatesWithoutAConnection(): void
    {
        $this->assertSame(0, $this->schema()->clear());
    }

    private function schema(): AbstractSchema
    {
        return new class extends AbstractSchema {
            protected string $table = 'widgets';

            protected function getStatementsMysql(): array
            {
                return ['mysql'];
            }

            protected function getStatementsPgsql(): array
            {
                return ['pgsql'];
            }

            protected function getStatementsSqlite(): array
            {
                return ['sqlite'];
            }
        };
    }
}
