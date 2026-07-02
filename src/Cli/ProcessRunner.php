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

namespace Phalcon\Talon\Cli;

use function getenv;
use function is_resource;
use function proc_close;
use function proc_open;

use const STDERR;
use const STDIN;
use const STDOUT;

/**
 * Spawns a child process with inherited stdio (preserves colors and TTY)
 * and returns its exit code verbatim. The only unit that touches process
 * APIs; deliberately not final so tests can substitute a recording fake.
 */
class ProcessRunner
{
    /**
     * @param list<string>          $command
     * @param array<string, string> $env
     */
    public function run(array $command, string $cwd, array $env = []): int
    {
        $environment = $env === [] ? null : [...getenv(), ...$env];

        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $cwd, $environment);
        if (!is_resource($process)) {
            return 1;
        }

        return proc_close($process);
    }
}
