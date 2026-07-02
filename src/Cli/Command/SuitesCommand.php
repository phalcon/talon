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

namespace Phalcon\Talon\Cli\Command;

use Phalcon\Talon\Cli\SuiteMap;

use function fwrite;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

use const PHP_EOL;
use const STDOUT;

/**
 * Lists every mapped suite with its config path (relative to the project
 * root when possible), flagging configs that do not exist and the default.
 */
final class SuitesCommand
{
    /**
     * @param resource $stdout
     */
    public function __construct(
        private readonly SuiteMap $map,
        private $stdout = STDOUT,
    ) {
    }

    public function execute(): int
    {
        $default = $this->map->defaultSuite();

        foreach ($this->map->suites() as $suite) {
            fwrite($this->stdout, sprintf(
                '%-12s %s%s%s%s',
                $suite->name,
                $this->relative($suite->config),
                $suite->configExists() ? '' : ' (missing)',
                $suite->name === $default ? ' (default)' : '',
                PHP_EOL
            ));
        }

        return 0;
    }

    private function relative(string $path): string
    {
        $prefix = $this->map->root() . '/';

        return str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path;
    }
}
