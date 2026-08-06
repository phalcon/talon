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
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function fopen;
use function glob;
use function is_dir;
use function rmdir;
use function unlink;

final class SchemaCommandTest extends TestCase
{
    private string $output = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = __DIR__ . '/../../_output/schema-command';
    }

    protected function tearDown(): void
    {
        foreach (['mysql', 'pgsql', 'sqlite'] as $driver) {
            $directory = $this->output . '/' . $driver;
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }

        // Any stray file at the top level - a dump left by an earlier format,
        // say - would make rmdir() warn, and failOnWarning turns that into a
        // failure with nothing to do with what the test asserted.
        foreach (glob($this->output . '/*.sql') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->output)) {
            rmdir($this->output);
        }

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

    public function testWritesOnlyTheRequestedDialect(): void
    {
        $this->assertSame(0, $this->command()->execute(['sqlite']));

        $this->assertFileExists($this->output . '/sqlite/manifest.json');
        $this->assertDirectoryDoesNotExist($this->output . '/mysql');
    }

    private function command(): SchemaCommand
    {
        $namespace = 'Phalcon\\Talon\\Tests\\Fixtures\\Schema';

        // fromArray() folds every key except root/db/paths/services into
        // `extra`, so the schema_* keys sit at the top level here.
        $settings = Settings::fromArray([
            'root'             => dirname(__DIR__, 3),
            'schema_source'    => 'tests/Fixtures/Schema',
            'schema_namespace' => $namespace,
            'schema_output'    => 'tests/_output/schema-command',
            'schema_pre'       => $namespace . '\\PreSchema',
            'schema_post'      => $namespace . '\\PostSchema',
        ]);

        $stdout = fopen('php://memory', 'rb+');
        $this->assertIsResource($stdout);

        return new SchemaCommand($settings, $stdout);
    }
}
