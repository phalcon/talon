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

namespace Phalcon\Talon\Tests\Unit\Cli;

use Phalcon\Talon\Cli\Command\SchemaCommand;
use Phalcon\Talon\Exceptions\UnknownDriver;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Traits\FileSystemTrait;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function fopen;
use function glob;
use function rewind;
use function rmdir;
use function stream_get_contents;

use const PHP_EOL;

final class SchemaCommandTest extends TestCase
{
    use FileSystemTrait;

    private string $output = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = __DIR__ . '/../../_output/schema-command';
    }

    protected function tearDown(): void
    {
        // Mutation runs leave debris under misspelled paths, including
        // dotfiles that glob() will not match. safeDeleteDirectory() walks
        // the tree itself, so nothing survives to make rmdir() warn.
        $this->safeDeleteDirectory($this->output);
        parent::tearDown();
    }

    public function testUnknownDriverArgumentThrows(): void
    {
        $this->expectException(UnknownDriver::class);

        $this->command()->execute(['oracle']);
    }

    public function testWritesEveryDialectByDefault(): void
    {
        $this->assertSame(0, $this->command()->execute());

        $this->assertFileExists($this->output . '/mysql/manifest.json');
        $this->assertFileExists($this->output . '/pgsql/manifest.json');
        $this->assertFileExists($this->output . '/sqlite/manifest.json');

        $this->assertStringContainsString(
            'DROP TABLE IF EXISTS `widgets`;',
            (string) file_get_contents($this->output . '/mysql/widgets.sql')
        );
    }

    public function testWritesEveryRequestedDialectAndReportsEachPath(): void
    {
        $stdout = fopen('php://memory', 'rb+');
        $this->assertIsResource($stdout);

        $this->assertSame(0, $this->command($stdout)->execute(['mysql', 'sqlite']));

        rewind($stdout);
        $reported = (string) stream_get_contents($stdout);

        $this->assertSame(
            'Wrote ' . $this->root() . '/tests/_output/schema-command/mysql' . PHP_EOL
            . 'Wrote ' . $this->root() . '/tests/_output/schema-command/sqlite' . PHP_EOL,
            $reported
        );

        $this->assertFileExists($this->output . '/mysql/manifest.json');
        $this->assertFileExists($this->output . '/sqlite/manifest.json');
        $this->assertDirectoryDoesNotExist($this->output . '/pgsql');
    }

    public function testWritesOnlyTheRequestedDialect(): void
    {
        $this->assertSame(0, $this->command()->execute(['sqlite']));

        $this->assertFileExists($this->output . '/sqlite/manifest.json');
        $this->assertDirectoryDoesNotExist($this->output . '/mysql');
    }

    /**
     * @param resource|null $stdout
     */
    private function command($stdout = null): SchemaCommand
    {
        $namespace = 'Phalcon\\Talon\\Tests\\Fixtures\\Schema';

        // fromArray() folds every key except root/db/paths/services into
        // `extra`, so the schema_* keys sit at the top level here.
        $settings = Settings::fromArray([
            'root'             => $this->root(),
            'schema_source'    => 'tests/Fixtures/Schema',
            'schema_namespace' => $namespace,
            'schema_output'    => 'tests/_output/schema-command',
            'schema_pre'       => $namespace . '\\PreSchema',
            'schema_post'      => $namespace . '\\PostSchema',
        ]);

        if ($stdout === null) {
            $stdout = fopen('php://memory', 'rb+');
            $this->assertIsResource($stdout);
        }

        return new SchemaCommand($settings, $stdout);
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
