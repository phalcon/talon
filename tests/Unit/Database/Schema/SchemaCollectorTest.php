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

use Phalcon\Talon\Database\Schema\SchemaCollector;
use Phalcon\Talon\Database\Schema\SchemaDefinition;
use Phalcon\Talon\Exceptions\SchemaClassNotFound;
use Phalcon\Talon\Tests\Fixtures\Schema\PostSchema;
use Phalcon\Talon\Tests\Fixtures\Schema\PreSchema;
use PHPUnit\Framework\TestCase;

use function array_map;
use function dirname;

final class SchemaCollectorTest extends TestCase
{
    private const FIXTURE_NAMESPACE = 'Phalcon\\Talon\\Tests\\Fixtures\\Schema';

    public function testDefinitionsAreSortedAndExcludePreAndPost(): void
    {
        $tables = array_map(
            static fn (SchemaDefinition $definition): string => $definition->getTable(),
            $this->collector()->definitions()
        );

        $this->assertSame(['albums', 'widgets'], $tables);
    }

    public function testMissingPreClassThrows(): void
    {
        $collector = new SchemaCollector(
            $this->directory(),
            self::FIXTURE_NAMESPACE,
            self::FIXTURE_NAMESPACE . '\\NoSuchSchema'
        );

        $this->expectException(SchemaClassNotFound::class);
        $this->expectExceptionMessage(
            "Schema class not found: 'Phalcon\\Talon\\Tests\\Fixtures\\Schema\\NoSuchSchema'"
        );

        $collector->preSchema();
    }

    public function testPreAndPostAreNullWhenUnset(): void
    {
        $collector = new SchemaCollector($this->directory(), self::FIXTURE_NAMESPACE);

        $this->assertNull($collector->preSchema());
        $this->assertNull($collector->postSchema());
    }

    public function testPreAndPostAreResolved(): void
    {
        $collector = $this->collector();

        $this->assertInstanceOf(PreSchema::class, $collector->preSchema());
        $this->assertInstanceOf(PostSchema::class, $collector->postSchema());
    }

    private function collector(): SchemaCollector
    {
        return new SchemaCollector(
            $this->directory(),
            self::FIXTURE_NAMESPACE,
            self::FIXTURE_NAMESPACE . '\\PreSchema',
            self::FIXTURE_NAMESPACE . '\\PostSchema'
        );
    }

    private function directory(): string
    {
        return dirname(__DIR__, 3) . '/Fixtures/Schema';
    }
}
