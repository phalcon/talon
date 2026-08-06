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
use Phalcon\Talon\Database\Schema\SchemaCollector;
use Phalcon\Talon\Database\Schema\SchemaDefinition;
use Phalcon\Talon\Database\Schema\SchemaManifest;
use Phalcon\Talon\Exceptions\SchemaManifestNotFound;
use Phalcon\Talon\Exceptions\SchemaTableNotFound;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function mkdir;
use function rmdir;
use function unlink;

final class SchemaManifestTest extends TestCase
{
    private const FIXTURE_NAMESPACE = 'Phalcon\\Talon\\Tests\\Fixtures\\Schema';

    private string $directory = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = dirname(__DIR__, 3) . '/_output/manifest-test';
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $file = $this->directory . '/' . SchemaManifest::FILE;
        if (file_exists($file)) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function testEncodeRecordsOrderDependenciesAndFiles(): void
    {
        $json = SchemaManifest::encode(Dialect::Sqlite, $this->definitions());

        $this->assertJson($json);
        $this->assertStringEndsWith("\n", $json);

        /** @var array{dialect: string, tables: array<int, array<string, mixed>>} $decoded */
        $decoded = json_decode($json, true);

        $this->assertSame('sqlite', $decoded['dialect']);
        $this->assertSame(
            [
                ['table' => 'albums', 'file' => 'albums.sql', 'dependencies' => ['widgets']],
                ['table' => 'widgets', 'file' => 'widgets.sql', 'dependencies' => []],
                ['table' => 'zones', 'file' => 'zones.sql', 'dependencies' => ['widgets']],
            ],
            $decoded['tables']
        );
    }

    public function testEncodeTakesSchemaQualifiedNamesVerbatim(): void
    {
        $qualified = new class extends AbstractSchema {
            protected string $table = 'private.co_orders_x_products';

            protected function getStatementsMysql(): array
            {
                return ['CREATE TABLE private.co_orders_x_products (id INT);'];
            }

            protected function getStatementsPgsql(): array
            {
                return ['CREATE TABLE private.co_orders_x_products (id INT);'];
            }

            protected function getStatementsSqlite(): array
            {
                return [];
            }
        };

        /** @var array{dialect: string, tables: array<int, array<string, mixed>>} $decoded */
        $decoded = json_decode(SchemaManifest::encode(Dialect::Mysql, [$qualified]), true);

        // The dot is part of the name, not a path separator - no normalizing.
        $this->assertSame(
            [
                [
                    'table'        => 'private.co_orders_x_products',
                    'file'         => 'private.co_orders_x_products.sql',
                    'dependencies' => [],
                ],
            ],
            $decoded['tables']
        );
    }

    public function testFromDirectoryReadsOrderAndDependencies(): void
    {
        $manifest = $this->write();

        $this->assertSame(['albums', 'widgets', 'zones'], $manifest->getTables());
        $this->assertSame([], $manifest->getDependencies('widgets'));
        $this->assertSame(['widgets'], $manifest->getDependencies('zones'));
        $this->assertSame($this->directory . '/zones.sql', $manifest->getFile('zones'));
    }

    public function testMissingManifestThrows(): void
    {
        $this->expectException(SchemaManifestNotFound::class);
        $this->expectExceptionMessage(
            "Schema manifest not found: '" . $this->directory . '/' . SchemaManifest::FILE . "'"
        );

        SchemaManifest::fromDirectory($this->directory);
    }

    public function testPreAndPostFilesAreResolvedPositionally(): void
    {
        $manifest = $this->write();

        $this->assertSame($this->directory . '/_preSchema.sql', $manifest->getPreSchemaFile());
        $this->assertSame($this->directory . '/_postSchema.sql', $manifest->getPostSchemaFile());
    }

    public function testUnknownTableThrows(): void
    {
        $manifest = $this->write();

        $this->expectException(SchemaTableNotFound::class);
        $this->expectExceptionMessage("Table 'nope' is not in the schema manifest");

        $manifest->getDependencies('nope');
    }

    /**
     * @return list<SchemaDefinition>
     */
    private function definitions(): array
    {
        return (new SchemaCollector(
            dirname(__DIR__, 3) . '/Fixtures/Schema',
            self::FIXTURE_NAMESPACE,
            self::FIXTURE_NAMESPACE . '\\PreSchema',
            self::FIXTURE_NAMESPACE . '\\PostSchema'
        ))->definitions();
    }

    private function write(): SchemaManifest
    {
        file_put_contents(
            $this->directory . '/' . SchemaManifest::FILE,
            SchemaManifest::encode(Dialect::Sqlite, $this->definitions())
        );

        return SchemaManifest::fromDirectory($this->directory);
    }
}
