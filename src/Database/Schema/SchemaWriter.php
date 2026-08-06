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

use function file_put_contents;
use function is_dir;
use function mkdir;
use function rtrim;

use const DIRECTORY_SEPARATOR;

/**
 * Renders one dialect into its own directory: pre-schema, a file per table,
 * the manifest, then post-schema. Pre and post are always written even when
 * empty - the loader must be able to tell "deliberately nothing" apart from
 * "the generator never ran".
 */
final class SchemaWriter
{
    public function __construct(
        private readonly SchemaCollector $collector,
        private readonly SchemaGenerator $generator,
    ) {
    }

    /**
     * @return string the dialect directory that was written
     */
    public function write(Dialect $dialect, string $directory): string
    {
        $directory = rtrim($directory, '/') . DIRECTORY_SEPARATOR . $dialect->value;
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $this->put($directory, SchemaManifest::PRE_SCHEMA, $this->generator->preSchema($dialect));

        $present = [];
        foreach ($this->collector->definitions() as $definition) {
            $sql = $this->generator->table($definition, $dialect);
            if ($sql === '') {
                continue;
            }

            $present[] = $definition;
            $this->put($directory, $definition->getTable() . '.sql', $sql);
        }

        $this->put($directory, SchemaManifest::FILE, SchemaManifest::encode($dialect, $present));
        $this->put($directory, SchemaManifest::POST_SCHEMA, $this->generator->postSchema($dialect));

        return $directory;
    }

    private function put(string $directory, string $name, string $contents): void
    {
        file_put_contents($directory . DIRECTORY_SEPARATOR . $name, $contents);
    }
}
