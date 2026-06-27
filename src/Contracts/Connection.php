<?php

declare(strict_types=1);

namespace Phalcon\Talon\Contracts;

use PDO;

interface Connection
{
    public function execute(string $sql): void;

    public function getPdo(): PDO;

    public function loadSchema(string $dumpFile): void;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    public function select(string $table, array $criteria = []): array;
}
