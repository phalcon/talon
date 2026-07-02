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

use Phalcon\Cop\Parser;

use function array_slice;
use function in_array;

/**
 * Splits raw argv into Talon's own command/arguments/options and a verbatim
 * passthrough tail for PHPUnit. The tail starts at "--" or at the first
 * option token Talon does not recognize, so suites come before options.
 * cli-options-parser stays behind this boundary.
 */
final class Input
{
    private const TALON_OPTIONS = ['--help', '-h', '--version', '-V'];

    /** @var array<array-key, mixed> */
    private array $options = [];

    /** @var list<string> */
    private array $passthrough = [];

    /** @var list<string> */
    private array $positionals = [];

    /**
     * @param list<string> $argv
     */
    private function __construct(array $argv)
    {
        $recognized = [];
        $inTail     = false;

        foreach (array_slice($argv, 1) as $token) {
            if ($inTail) {
                $this->passthrough[] = $token;

                continue;
            }

            if ($token === '--') {
                $inTail = true;

                continue;
            }

            if ($token !== '' && $token[0] === '-') {
                if (in_array($token, self::TALON_OPTIONS, true)) {
                    $recognized[] = $token;

                    continue;
                }

                $inTail              = true;
                $this->passthrough[] = $token;

                continue;
            }

            $this->positionals[] = $token;
        }

        $this->options = (new Parser())->parse(['talon', ...$recognized]);
    }

    /**
     * @param list<string> $argv
     */
    public static function fromArgv(array $argv): self
    {
        return new self($argv);
    }

    /**
     * @return list<string>
     */
    public function arguments(): array
    {
        return array_slice($this->positionals, 1);
    }

    public function command(): ?string
    {
        return $this->positionals[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function passthrough(): array
    {
        return $this->passthrough;
    }

    public function wantsHelp(): bool
    {
        return (bool) ($this->options['help'] ?? $this->options['h'] ?? false);
    }

    public function wantsVersion(): bool
    {
        return (bool) ($this->options['version'] ?? $this->options['V'] ?? false);
    }
}
