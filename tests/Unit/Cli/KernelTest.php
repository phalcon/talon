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

use Composer\InstalledVersions;
use Phalcon\Talon\Cli\Kernel;
use PHPUnit\Framework\TestCase;

use function fopen;
use function rewind;
use function stream_get_contents;

use const PHP_EOL;

final class KernelTest extends TestCase
{
    /** @var resource */
    private $stderr;

    /** @var resource */
    private $stdout;

    protected function setUp(): void
    {
        $out = fopen('php://memory', 'w+');
        $err = fopen('php://memory', 'w+');
        $this->assertNotFalse($out);
        $this->assertNotFalse($err);
        $this->stdout = $out;
        $this->stderr = $err;
    }

    public function testHelpAndBareInvocationPrintUsage(): void
    {
        $this->assertSame(0, $this->kernel()->handle(['talon', '--help']));
        $this->assertStringContainsString('Usage:', $this->stream($this->stdout));

        $this->assertSame(0, $this->kernel()->handle(['talon']));
    }

    public function testSuitesListsTheRepoSuites(): void
    {
        $code = $this->kernel()->handle(['talon', 'suites']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('mysql', $this->stream($this->stdout));
    }

    public function testUnknownCommandFails(): void
    {
        $code = $this->kernel()->handle(['talon', 'brew']);

        $this->assertSame(1, $code);
        $this->assertStringStartsWith(
            "talon: unknown command 'brew'" . PHP_EOL,
            $this->stream($this->stderr)
        );
        $this->assertStringContainsString('Usage:', $this->stream($this->stderr));
    }

    public function testUnknownSuiteRendersACleanError(): void
    {
        $code = $this->kernel()->handle(['talon', 'run', 'oracle']);

        $this->assertSame(1, $code);
        $this->assertSame(
            "talon: Unknown suite 'oracle'. Available suites: mysql, pgsql, sqlite, unit" . PHP_EOL,
            $this->stream($this->stderr)
        );
    }

    public function testVersion(): void
    {
        $code = $this->kernel()->handle(['talon', '--version']);

        $this->assertSame(0, $code);
        $this->assertSame(
            'Talon ' . (InstalledVersions::getPrettyVersion('phalcon/talon') ?? 'dev') . PHP_EOL,
            $this->stream($this->stdout)
        );
    }

    private function kernel(): Kernel
    {
        return new Kernel($this->stdout, $this->stderr);
    }

    /**
     * @param resource $stream
     */
    private function stream($stream): string
    {
        rewind($stream);

        return (string) stream_get_contents($stream);
    }
}
