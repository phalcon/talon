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

namespace Phalcon\Talon\Database\Schema;

use Phalcon\Talon\Database\Dialect;

use function implode;
use function str_ends_with;
use function trim;

/**
 * Renders one dialect's dump: pre-schema, then every table as its own DROP
 * followed by its creation statements, then post-schema.
 */
final class SchemaGenerator
{
    public function __construct(private readonly SchemaCollector $collector)
    {
    }

    public function generate(Dialect $dialect): string
    {
        $statements = [];

        $pre = $this->collector->preSchema();
        if ($pre !== null) {
            foreach ($pre->getStatements($dialect) as $statement) {
                $statements[] = $this->terminate($statement);
            }
        }

        foreach ($this->collector->definitions() as $definition) {
            $own = $definition->getStatements($dialect);

            // No statements means this table does not exist in this dialect,
            // so it gets no DROP either.
            if ($own === []) {
                continue;
            }

            $statements[] = 'DROP TABLE IF EXISTS '
                . $dialect->quoteIdentifier($definition->getTable()) . ';';

            foreach ($own as $statement) {
                $statements[] = $this->terminate($statement);
            }
        }

        $post = $this->collector->postSchema();
        if ($post !== null) {
            foreach ($post->getStatements($dialect) as $statement) {
                $statements[] = $this->terminate($statement);
            }
        }

        return implode("\n\n", $statements) . "\n";
    }

    private function terminate(string $statement): string
    {
        $statement = trim($statement);

        return str_ends_with($statement, ';') ? $statement : $statement . ';';
    }
}
