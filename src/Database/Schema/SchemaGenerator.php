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

use function array_filter;
use function array_values;
use function implode;
use function str_ends_with;
use function trim;

/**
 * Renders one dialect's schema as text, either whole or one part at a time:
 * pre-schema, then every table as its own DROP followed by its creation
 * statements, then post-schema.
 */
final class SchemaGenerator
{
    public function __construct(private readonly SchemaCollector $collector)
    {
    }

    public function generate(Dialect $dialect): string
    {
        $parts = [$this->preSchema($dialect)];

        foreach ($this->collector->definitions() as $definition) {
            $parts[] = $this->table($definition, $dialect);
        }

        $parts[] = $this->postSchema($dialect);

        return implode(
            "\n",
            array_values(array_filter($parts, static fn (string $part): bool => $part !== ''))
        );
    }

    public function postSchema(Dialect $dialect): string
    {
        $post = $this->collector->postSchema();

        return $post === null ? '' : $this->render($post->getStatements($dialect));
    }

    public function preSchema(Dialect $dialect): string
    {
        $pre = $this->collector->preSchema();

        return $pre === null ? '' : $this->render($pre->getStatements($dialect));
    }

    /**
     * The DROP is the generator's, not the definition's - it already knows the
     * table name and is the only consumer that needs idempotency. An empty
     * statement list means the table is absent from this dialect, so it gets
     * no DROP either.
     */
    public function table(SchemaDefinition $definition, Dialect $dialect): string
    {
        $own = $definition->getStatements($dialect);

        if ($own === []) {
            return '';
        }

        return $this->render([
            'DROP TABLE IF EXISTS ' . $dialect->quoteIdentifier($definition->getTable()) . ';',
            ...$own,
        ]);
    }

    /**
     * @param list<string> $statements
     */
    private function render(array $statements): string
    {
        if ($statements === []) {
            return '';
        }

        $terminated = [];
        foreach ($statements as $statement) {
            $terminated[] = $this->terminate($statement);
        }

        return implode("\n\n", $terminated) . "\n";
    }

    private function terminate(string $statement): string
    {
        $statement = trim($statement);

        return str_ends_with($statement, ';') ? $statement : $statement . ';';
    }
}
