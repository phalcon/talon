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

final class AlbumSchema extends AbstractSchema
{
    protected string $table = 'albums';

    public function getDependencies(): array
    {
        return ['widgets'];
    }

    protected function getStatementsMysql(): array
    {
        return [
            'CREATE TABLE albums (id INT PRIMARY KEY, title VARCHAR(64));',
            'CREATE INDEX albums_title_index ON albums (title);',
        ];
    }

    protected function getStatementsPgsql(): array
    {
        return [
            'CREATE TABLE albums (id INTEGER PRIMARY KEY, title VARCHAR(64));',
            // Deliberately unterminated - the generator must add the semicolon.
            'CREATE INDEX albums_title_index ON albums (title)',
        ];
    }

    protected function getStatementsSqlite(): array
    {
        return [];
    }
}
