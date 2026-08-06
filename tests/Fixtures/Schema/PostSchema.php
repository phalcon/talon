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

namespace Phalcon\Talon\Tests\Fixtures\Schema;

use Phalcon\Talon\Database\Schema\AbstractSchema;

final class PostSchema extends AbstractSchema
{
    protected string $table = '';

    protected function getStatementsMysql(): array
    {
        return ['SET FOREIGN_KEY_CHECKS=1;'];
    }

    protected function getStatementsPgsql(): array
    {
        return [];
    }

    protected function getStatementsSqlite(): array
    {
        return [];
    }
}
