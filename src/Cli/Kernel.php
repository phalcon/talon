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

use Composer\InstalledVersions;
use Phalcon\Talon\Cli\Command\RunCommand;
use Phalcon\Talon\Cli\Command\SchemaCommand;
use Phalcon\Talon\Cli\Command\SuitesCommand;
use Phalcon\Talon\Exceptions\Exception;
use Phalcon\Talon\Talon;

use function fwrite;
use function getenv;
use function stream_isatty;

use const PHP_EOL;
use const STDERR;
use const STDOUT;

/**
 * The command registry and dispatcher: resolves argv to a command, renders
 * help/version/usage, and turns Talon exceptions into clean stderr errors.
 */
final class Kernel
{
    private const COLOR_RESET = "\033[0m";
    private const COLOR_TEAL  = "\033[38;5;36m";
    private const MARK        = '(((';
    private const PACKAGE     = 'phalcon/talon';

    private bool $decorated;

    /**
     * @param resource  $stdout
     * @param resource  $stderr
     * @param bool|null $decorated Null auto-detects from NO_COLOR and tty.
     */
    public function __construct(
        private $stdout = STDOUT,
        private $stderr = STDERR,
        ?bool $decorated = null,
    ) {
        $this->decorated = $decorated ?? $this->detectDecoration($stdout);
    }

    /**
     * @param list<string> $argv
     */
    public function handle(array $argv): int
    {
        $input = Input::fromArgv($argv);

        if ($input->wantsVersion()) {
            fwrite($this->stdout, 'Talon ' . $this->version() . PHP_EOL);

            return 0;
        }

        $command = $input->command();
        if ($input->wantsHelp() || $command === null) {
            fwrite($this->stdout, $this->usage());

            return 0;
        }

        try {
            return match ($command) {
                'run'    => (new RunCommand(SuiteMap::locate(), new ProcessRunner(), $this->stdout))
                    ->execute($input),
                'schema' => (new SchemaCommand(Talon::settings(), $this->stdout))
                    ->execute($input->arguments()),
                'suites' => (new SuitesCommand(SuiteMap::locate(), $this->stdout))->execute(),
                default  => $this->unknownCommand($command),
            };
        } catch (Exception $exception) {
            fwrite($this->stderr, 'talon: ' . $exception->getMessage() . PHP_EOL);

            return 1;
        }
    }

    /**
     * The identity line a run opens with: the claw mark, the tool name and its
     * version. The mark carries no escapes when the output is piped or
     * NO_COLOR is set, so only the glyph survives.
     */
    private function banner(): string
    {
        return $this->mark() . ' talon ' . $this->version();
    }

    /**
     * @param resource $stream
     */
    private function detectDecoration($stream): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        return stream_isatty($stream);
    }

    private function mark(): string
    {
        if (false === $this->decorated) {
            return self::MARK;
        }

        return self::COLOR_TEAL . self::MARK . self::COLOR_RESET;
    }

    private function unknownCommand(string $command): int
    {
        fwrite($this->stderr, "talon: unknown command '{$command}'" . PHP_EOL . $this->usage());

        return 1;
    }

    private function usage(): string
    {
        return $this->banner() . PHP_EOL . <<<'USAGE'

            Usage:
              talon run [suites...] [-- passthrough]   Run mapped PHPUnit suite(s)
              talon schema [drivers...]                Generate SQL schema dumps
              talon suites                             List mapped suites
              talon --help | --version

            Options are forwarded to PHPUnit starting at the first option talon
            does not recognize itself; everything after '--' is always forwarded
            verbatim. The reserved suite name 'all' runs every mapped suite.

            USAGE;
    }

    private function version(): string
    {
        return InstalledVersions::isInstalled(self::PACKAGE)
            ? (InstalledVersions::getPrettyVersion(self::PACKAGE) ?? 'dev')
            : 'dev';
    }
}
