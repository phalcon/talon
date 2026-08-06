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
use Phalcon\Talon\Database\Schema\SchemaCollector;
use Phalcon\Talon\Database\Schema\SchemaGenerator;
use Phalcon\Talon\Database\StatementSplitter;
use PHPUnit\Framework\TestCase;

use function dirname;
use function str_ends_with;
use function substr_count;

final class SchemaGeneratorTest extends TestCase
{
    private const FIXTURE_NAMESPACE = 'Phalcon\\Talon\\Tests\\Fixtures\\Schema';

    public function testDropIsPrependedPerTable(): void
    {
        $sql = $this->generator()->generate(Dialect::Mysql);

        $this->assertStringContainsString('DROP TABLE IF EXISTS `albums`;', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `widgets`;', $sql);
        $this->assertSame(2, substr_count($sql, 'DROP TABLE IF EXISTS'));
    }

    public function testEmptyDialectSkipsTheTableEntirely(): void
    {
        $sql = $this->generator()->generate(Dialect::Sqlite);

        $this->assertStringNotContainsString('albums', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS "widgets";', $sql);
    }

    public function testEndsWithANewline(): void
    {
        $this->assertTrue(str_ends_with($this->generator()->generate(Dialect::Mysql), "\n"));
    }

    public function testGeneratedSqlSplitsBackIntoDiscreteStatements(): void
    {
        $sql = $this->generator()->generate(Dialect::Sqlite);

        $statements = StatementSplitter::split($sql);

        $this->assertSame(
            [
                'DROP TABLE IF EXISTS "widgets"',
                'CREATE TABLE widgets (id INTEGER PRIMARY KEY, label TEXT)',
            ],
            $statements
        );
    }

    public function testPreComesFirstAndPostComesLast(): void
    {
        $sql = $this->generator()->generate(Dialect::Mysql);

        $this->assertStringStartsWith('SET FOREIGN_KEY_CHECKS=0;', $sql);
        $this->assertStringEndsWith("SET FOREIGN_KEY_CHECKS=1;\n", $sql);
    }

    public function testUnterminatedStatementsGainASemicolon(): void
    {
        $sql = $this->generator()->generate(Dialect::Pgsql);

        // AlbumSchema's pgsql index statement is declared without a semicolon.
        $this->assertStringContainsString(
            'CREATE INDEX albums_title_index ON albums (title);',
            $sql
        );
        $this->assertStringNotContainsString(';;', $sql);
    }

    private function generator(): SchemaGenerator
    {
        return new SchemaGenerator(new SchemaCollector(
            dirname(__DIR__, 3) . '/Fixtures/Schema',
            self::FIXTURE_NAMESPACE,
            self::FIXTURE_NAMESPACE . '\\PreSchema',
            self::FIXTURE_NAMESPACE . '\\PostSchema'
        ));
    }
}
