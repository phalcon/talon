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

namespace Phalcon\Talon\Database;

use PDO;
use Phalcon\Talon\Exceptions\UnknownDriver;

use function explode;
use function implode;
use function is_string;
use function str_replace;

enum Dialect: string
{
    case Mysql  = 'mysql';
    case Pgsql  = 'pgsql';
    case Sqlite = 'sqlite';

    /**
     * MariaDB connects through pdo_mysql, so PDO reports 'mysql' for it. That
     * is correct here - the SQL dialect is the same. Use the configured driver
     * name when a test needs to tell the two servers apart.
     */
    public static function fromPdo(PDO $pdo): self
    {
        $name = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $name = is_string($name) ? $name : '';

        return self::tryFrom($name) ?? throw new UnknownDriver($name);
    }

    public function quoteIdentifier(string $name): string
    {
        $delimiter = match ($this) {
            self::Mysql               => '`',
            self::Pgsql, self::Sqlite => '"',
        };

        $quoted = [];
        foreach (explode('.', $name) as $segment) {
            $quoted[] = $delimiter
                . str_replace($delimiter, $delimiter . $delimiter, $segment)
                . $delimiter;
        }

        return implode('.', $quoted);
    }
}
