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

namespace Phalcon\Talon\Tests\Fakes\Cli;

use Phalcon\Talon\Cli\ProcessRunner;

use function array_shift;

/**
 * Records every run() call instead of spawning a process and answers with
 * scripted exit codes (0 when the script is exhausted).
 */
final class RecordingProcessRunner extends ProcessRunner
{
    /** @var list<array{command: list<string>, cwd: string, env: array<string, string>}> */
    public array $calls = [];

    /** @var list<int> */
    public array $exitCodes = [];

    public function run(array $command, string $cwd, array $env = []): int
    {
        $this->calls[] = ['command' => $command, 'cwd' => $cwd, 'env' => $env];

        return array_shift($this->exitCodes) ?? 0;
    }
}
