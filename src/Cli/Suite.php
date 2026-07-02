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

use function file_exists;

/**
 * An immutable suite specification: the PHPUnit config it maps to plus the
 * php ini flags, environment variables and default arguments its runs use.
 */
final class Suite
{
    /**
     * @param list<string>          $phpFlags
     * @param array<string, string> $env
     * @param list<string>          $args
     */
    public function __construct(
        public readonly string $name,
        public readonly string $config,
        public readonly array $phpFlags = [],
        public readonly array $env = [],
        public readonly array $args = [],
    ) {
    }

    public function configExists(): bool
    {
        return file_exists($this->config);
    }
}
