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

use Phalcon\Talon\Exceptions\SchemaClassNotFound;
use ReflectionClass;

use function basename;
use function class_exists;
use function glob;
use function rtrim;
use function sort;

/**
 * Discovers schema definitions in a directory. Pre/post schema classes are
 * resolved separately - they are positional, not part of the ordered set.
 */
final class SchemaCollector
{
    public function __construct(
        private readonly string $directory,
        private readonly string $namespace,
        private readonly string $pre = '',
        private readonly string $post = '',
    ) {
    }

    /**
     * Intermediate definitions only, sorted by file name.
     *
     * @return list<SchemaDefinition>
     */
    public function definitions(): array
    {
        $files = glob(rtrim($this->directory, '/') . '/*.php') ?: [];
        sort($files);

        $definitions = [];
        foreach ($files as $file) {
            $class = $this->namespace . '\\' . basename($file, '.php');

            if ($class === $this->pre || $class === $this->post) {
                continue;
            }

            $definition = $this->instantiate($class);
            if ($definition !== null) {
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    public function postSchema(): ?SchemaDefinition
    {
        return $this->named($this->post);
    }

    public function preSchema(): ?SchemaDefinition
    {
        return $this->named($this->pre);
    }

    private function instantiate(string $class): ?SchemaDefinition
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);
        if (
            $reflection->isAbstract()
            || !$reflection->implementsInterface(SchemaDefinition::class)
        ) {
            return null;
        }

        /** @var SchemaDefinition $instance */
        $instance = $reflection->newInstance();

        return $instance;
    }

    private function named(string $class): ?SchemaDefinition
    {
        if ($class === '') {
            return null;
        }

        return $this->instantiate($class) ?? throw new SchemaClassNotFound($class);
    }
}
