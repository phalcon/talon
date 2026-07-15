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

use Phalcon\Talon\Cli\Input;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function testAllowlistedOptionsCombine(): void
    {
        $input = Input::fromArgv(['talon', '-h', '--version']);

        $this->assertTrue($input->wantsHelp());
        $this->assertTrue($input->wantsVersion());
    }
    public function testCommandAndArguments(): void
    {
        $input = Input::fromArgv(['talon', 'run', 'mysql', 'pgsql']);

        $this->assertSame('run', $input->command());
        $this->assertSame(['mysql', 'pgsql'], $input->arguments());
        $this->assertSame([], $input->passthrough());
        $this->assertFalse($input->wantsHelp());
        $this->assertFalse($input->wantsVersion());
    }

    public function testDashDashProtectsAllowlistedTokens(): void
    {
        // After '--' even talon's own flags forward verbatim.
        $input = Input::fromArgv(['talon', 'run', '--', 'unit', '-h']);

        $this->assertSame([], $input->arguments());
        $this->assertSame(['unit', '-h'], $input->passthrough());
        $this->assertFalse($input->wantsHelp());
    }

    public function testDashDashStartsThePassthroughTail(): void
    {
        $input = Input::fromArgv(['talon', 'run', '--', '--testdox', 'unit']);

        $this->assertSame('run', $input->command());
        $this->assertSame([], $input->arguments());
        $this->assertSame(['--testdox', 'unit'], $input->passthrough());
    }

    public function testEmptyArgvHasNoCommand(): void
    {
        $input = Input::fromArgv(['talon']);

        $this->assertNull($input->command());
        $this->assertSame([], $input->arguments());
        $this->assertSame([], $input->passthrough());
    }

    public function testEverythingAfterAnUnknownOptionIsPassthrough(): void
    {
        $input = Input::fromArgv(['talon', 'run', '--testdox', 'unit', '-h']);

        $this->assertSame('run', $input->command());
        $this->assertSame([], $input->arguments());
        $this->assertSame(['--testdox', 'unit', '-h'], $input->passthrough());
        $this->assertFalse($input->wantsHelp());
    }

    public function testUnknownEqualsOptionIsPassthrough(): void
    {
        $input = Input::fromArgv(['talon', 'run', 'unit', '--filter=FooTest']);

        $this->assertSame(['--filter=FooTest'], $input->passthrough());
    }

    public function testUnknownOptionWithValueIsPassthrough(): void
    {
        $input = Input::fromArgv(['talon', 'run', 'unit', '--filter', 'FooTest']);

        $this->assertSame(['unit'], $input->arguments());
        $this->assertSame(['--filter', 'FooTest'], $input->passthrough());
    }

    public function testWantsHelp(): void
    {
        $this->assertTrue(Input::fromArgv(['talon', '--help'])->wantsHelp());
        $this->assertTrue(Input::fromArgv(['talon', '-h'])->wantsHelp());
    }

    public function testWantsVersion(): void
    {
        $this->assertTrue(Input::fromArgv(['talon', '--version'])->wantsVersion());
        $this->assertTrue(Input::fromArgv(['talon', '-V'])->wantsVersion());
    }
}
