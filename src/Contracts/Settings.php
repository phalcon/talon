<?php

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
    public function getRedisOptions(): array;

    public function path(string $relative = ''): string;
}
