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

use Phalcon\Talon\Cli\Input;
use Phalcon\Talon\Cli\ProcessRunner;
use Phalcon\Talon\Cli\Suite;
use Phalcon\Talon\Cli\SuiteMap;

use function array_keys;
use function array_map;
use function count;
use function fwrite;
use function max;
use function sprintf;

use const PHP_BINARY;
use const PHP_EOL;
use const STDOUT;

/**
 * Resolves the requested suites (default suite when none given, every
 * mapped suite for the reserved name "all"), runs each as its own PHPUnit
 * subprocess and aggregates: a single suite's exit code is forwarded
 * verbatim, multiple suites report a summary and exit with the maximum.
 */
final class RunCommand
{
    /**
     * @param resource $stdout
     */
    public function __construct(
        private readonly SuiteMap $map,
        private readonly ProcessRunner $runner,
        private $stdout = STDOUT,
    ) {
    }

    public function execute(Input $input): int
    {
        $names = $input->arguments();
        if ($names === []) {
            $names = [$this->map->defaultSuite()];
        }

        if ($names === ['all']) {
            $names = array_keys($this->map->suites());
        }

        $suites = array_map(
            fn (string $name): Suite => $this->map->resolve($name),
            $names
        );

        $exitCode = 0;
        $results  = [];
        foreach ($suites as $suite) {
            $code = $this->runner->run(
                $this->command($suite, $input->passthrough()),
                $this->map->root(),
                $suite->env
            );

            $results[$suite->name] = $code;

            $exitCode = max($exitCode, $code);
        }

        if (count($results) > 1) {
            $this->summarize($results);
        }

        return $exitCode;
    }

    /**
     * @param list<string> $passthrough
     *
     * @return list<string>
     */
    private function command(Suite $suite, array $passthrough): array
    {
        $command = [PHP_BINARY];
        foreach ($suite->phpFlags as $flag) {
            $command[] = '-d';
            $command[] = $flag;
        }

        $command[] = $this->map->root() . '/vendor/bin/phpunit';
        $command[] = '--configuration';
        $command[] = $suite->config;

        return [...$command, ...$suite->args, ...$passthrough];
    }

    /**
     * @param array<string, int> $results
     */
    private function summarize(array $results): void
    {
        fwrite($this->stdout, PHP_EOL);
        foreach ($results as $name => $code) {
            fwrite($this->stdout, sprintf(
                '%-12s %s%s',
                $name,
                $code === 0 ? 'OK' : 'FAILED (exit ' . $code . ')',
                PHP_EOL
            ));
        }
    }
}
