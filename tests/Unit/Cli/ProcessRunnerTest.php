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

use Phalcon\Talon\Cli\ProcessRunner;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function realpath;
use function uniqid;
use function unlink;

use const PHP_BINARY;

final class ProcessRunnerTest extends TestCase
{
    public function testFailingToSpawnReportsExitCodeOne(): void
    {
        $runner = new class () extends ProcessRunner {
            protected function open(array $command, string $cwd, ?array $environment)
            {
                return false;
            }
        };

        $this->assertSame(1, $runner->run([PHP_BINARY, '-r', 'exit(0);'], dirname(__DIR__, 3)));
    }

    public function testForwardsTheExitCode(): void
    {
        $code = (new ProcessRunner())->run([PHP_BINARY, '-r', 'exit(7);'], dirname(__DIR__, 3));

        $this->assertSame(7, $code);
    }

    public function testPassesExtraEnvAndInheritsTheRest(): void
    {
        $dir  = dirname(__DIR__, 2) . '/_output';
        $file = $dir . '/env-' . uniqid() . '.txt';

        try {
            $probe = 'file_put_contents($argv[1], getenv("TALON_PROBE")'
                . ' . "|" . (getenv("PATH") !== false ? "path" : ""));';

            (new ProcessRunner())->run(
                [PHP_BINARY, '-r', $probe, $file],
                $dir,
                ['TALON_PROBE' => 'probe-value']
            );

            $this->assertSame('probe-value|path', file_get_contents($file));
        } finally {
            unlink($file);
        }
    }

    public function testRunsInTheGivenWorkingDirectory(): void
    {
        $dir  = dirname(__DIR__, 2) . '/_output';
        $file = $dir . '/cwd-' . uniqid() . '.txt';

        try {
            (new ProcessRunner())->run(
                [PHP_BINARY, '-r', 'file_put_contents($argv[1], getcwd());', $file],
                $dir
            );

            $this->assertSame(realpath($dir), file_get_contents($file));
        } finally {
            unlink($file);
        }
    }
}
