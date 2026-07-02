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

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function implode;

use const PHP_BINARY;

final class TalonBinTest extends TestCase
{
    public function testBinaryDiscoversTheRootFromANestedCwd(): void
    {
        // Root discovery must walk upward when invoked from a subdirectory.
        $nested = dirname(__DIR__, 3) . '/tests/_output';

        exec('cd ' . escapeshellarg($nested) . ' && ' . $this->binary() . ' suites 2>&1', $output, $code);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('mysql', implode("\n", $output));
    }

    public function testBinaryListsSuites(): void
    {
        exec($this->binary() . ' suites 2>&1', $output, $code);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('mysql', implode("\n", $output));
    }

    public function testBinaryPrintsVersion(): void
    {
        exec($this->binary() . ' --version 2>&1', $output, $code);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Talon', implode("\n", $output));
    }

    private function binary(): string
    {
        return escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/talon');
    }
}
