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
use Phalcon\Talon\Database\Schema\SchemaDefinition;
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
        $this->assertStringContainsString('DROP TABLE IF EXISTS `zones`;', $sql);
        $this->assertSame(3, substr_count($sql, 'DROP TABLE IF EXISTS'));
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
                'DROP TABLE IF EXISTS "zones"',
                'CREATE TABLE zones (id INTEGER PRIMARY KEY, widget_id INTEGER)',
            ],
            $statements
        );
    }

    public function testPartsConcatenateIntoGenerate(): void
    {
        $generator = $this->generator();

        $parts = $generator->preSchema(Dialect::Mysql);
        foreach ($this->definitions() as $definition) {
            $parts .= "\n" . $generator->table($definition, Dialect::Mysql);
        }
        $parts .= "\n" . $generator->postSchema(Dialect::Mysql);

        $this->assertSame($generator->generate(Dialect::Mysql), $parts);
    }

    public function testPostSchemaIsEmptyWhenTheDialectHasNoStatements(): void
    {
        $generator = $this->generator();

        $this->assertSame("SET FOREIGN_KEY_CHECKS=1;\n", $generator->postSchema(Dialect::Mysql));
        $this->assertSame('', $generator->postSchema(Dialect::Sqlite));
        $this->assertSame('', $generator->preSchema(Dialect::Sqlite));
    }

    public function testPreComesFirstAndPostComesLast(): void
    {
        $sql = $this->generator()->generate(Dialect::Mysql);

        $this->assertStringStartsWith('SET FOREIGN_KEY_CHECKS=0;', $sql);
        $this->assertStringEndsWith("SET FOREIGN_KEY_CHECKS=1;\n", $sql);
    }

    public function testTableIsEmptyWhenAbsentFromTheDialect(): void
    {
        $albums = $this->definitions()[0];

        $this->assertSame('albums', $albums->getTable());
        $this->assertSame('', $this->generator()->table($albums, Dialect::Sqlite));
    }

    public function testTableRendersDropThenStatements(): void
    {
        $widgets = $this->definitions()[1];

        $this->assertSame('widgets', $widgets->getTable());
        $this->assertSame(
            "DROP TABLE IF EXISTS \"widgets\";\n\n"
            . "CREATE TABLE widgets (id INTEGER PRIMARY KEY, label TEXT);\n",
            $this->generator()->table($widgets, Dialect::Sqlite)
        );
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

    private function collector(): SchemaCollector
    {
        return new SchemaCollector(
            dirname(__DIR__, 3) . '/Fixtures/Schema',
            self::FIXTURE_NAMESPACE,
            self::FIXTURE_NAMESPACE . '\\PreSchema',
            self::FIXTURE_NAMESPACE . '\\PostSchema'
        );
    }

    /**
     * @return list<SchemaDefinition>
     */
    private function definitions(): array
    {
        return $this->collector()->definitions();
    }

    private function generator(): SchemaGenerator
    {
        return new SchemaGenerator($this->collector());
    }
}
