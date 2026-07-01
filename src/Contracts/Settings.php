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

namespace Phalcon\Talon\Contracts;

interface Settings
{
    public function get(string $key, mixed $default = null): mixed;

    public function getDatabaseDsn(string $driver): string;

    /**
     * @return array<string, mixed>
     */
    public function getDatabaseOptions(string $driver): array;

    /**
     * @return array<string, mixed>
     */
    public function getMemcachedOptions(): array;

    /**
     * @return array<string, mixed>
     */
    public function getRedisClusterOptions(): array;

    /**
     * @return array<string, mixed>
     */
    public function getRedisOptions(): array;

    public function cachePath(string $relative = ''): string;

    public function dataPath(string $relative = ''): string;

    public function logsPath(string $relative = ''): string;

    public function outputPath(string $relative = ''): string;

    public function rootPath(string $relative = ''): string;

    public function supportPath(string $relative = ''): string;

    public function testsPath(string $relative = ''): string;
}
