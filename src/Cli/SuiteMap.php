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

namespace Phalcon\Talon\Cli;

use Phalcon\Talon\Exceptions\InvalidConfiguration;
use Phalcon\Talon\Exceptions\UnknownSuite;
use Phalcon\Talon\Settings;

use function array_key_first;
use function array_keys;
use function array_map;
use function array_values;
use function basename;
use function file_exists;
use function glob;
use function is_array;
use function is_scalar;
use function is_string;
use function preg_replace;
use function str_starts_with;

/**
 * Resolves suite names to invocation specs: from an explicit talon.php map
 * at the project root or, with zero configuration, from discovered
 * phpunit*.xml files (phpunit.mysql.xml -> mysql; phpunit.xml.dist -> unit,
 * the default). The only unit that reads configuration.
 */
final class SuiteMap
{
    private const CONFIG_FILE = 'talon.php';
    private const RESERVED    = 'all';

    private string $default = '';

    /** @var array<string, Suite> */
    private array $suites = [];

    public function __construct(private readonly string $root)
    {
        $file = $this->root . '/' . self::CONFIG_FILE;

        if (file_exists($file)) {
            $this->fromConfig(require $file);

            return;
        }

        $this->discover();
    }

    public static function locate(): self
    {
        return new self(Settings::fromEnv()->rootPath());
    }

    public function defaultSuite(): string
    {
        return $this->default;
    }

    public function resolve(string $name): Suite
    {
        return $this->suites[$name]
            ?? throw new UnknownSuite($name, array_keys($this->suites));
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * @return array<string, Suite>
     */
    public function suites(): array
    {
        return $this->suites;
    }

    private function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : $this->root . '/' . $path;
    }

    private function discover(): void
    {
        /** @var list<string> $files */
        $files = [
            ...(glob($this->root . '/phpunit*.xml*') ?: []),
            ...(glob($this->root . '/resources/phpunit*.xml*') ?: []),
        ];

        foreach ($files as $file) {
            $name = $this->suiteNameFor($file);
            if ($name === self::RESERVED || isset($this->suites[$name])) {
                continue;
            }

            $this->suites[$name] = new Suite($name, $file);
        }

        if ($this->suites === []) {
            throw new InvalidConfiguration(
                'no talon.php and no phpunit*.xml configuration files found under ' . $this->root
            );
        }

        $this->default = isset($this->suites['unit'])
            ? 'unit'
            : (string) array_key_first($this->suites);
    }

    private function fromConfig(mixed $config): void
    {
        if (!is_array($config)) {
            throw new InvalidConfiguration('talon.php must return an array');
        }

        $entries = $config['suites'] ?? null;
        if (!is_array($entries) || $entries === []) {
            throw new InvalidConfiguration("talon.php must define a non-empty 'suites' array");
        }

        $globalPhp = $this->stringList($config['php'] ?? [], "'php'");
        $globalEnv = $this->stringMap($config['env'] ?? [], "'env'");

        foreach ($entries as $name => $entry) {
            $name = (string) $name;
            if ($name === self::RESERVED) {
                throw new InvalidConfiguration("'all' is a reserved suite name");
            }

            if (!is_array($entry) || !isset($entry['config']) || !is_string($entry['config'])) {
                throw new InvalidConfiguration("suite '{$name}' must define a 'config' path");
            }

            $this->suites[$name] = new Suite(
                $name,
                $this->absolute($entry['config']),
                [...$globalPhp, ...$this->stringList($entry['php'] ?? [], "suite '{$name}' 'php'")],
                [...$globalEnv, ...$this->stringMap($entry['env'] ?? [], "suite '{$name}' 'env'")],
                $this->stringList($entry['args'] ?? [], "suite '{$name}' 'args'"),
            );
        }

        $default = $config['default']
            ?? (isset($this->suites['unit']) ? 'unit' : array_key_first($this->suites));
        if (!is_string($default) || !isset($this->suites[$default])) {
            throw new InvalidConfiguration("the default suite is not defined in 'suites'");
        }

        $this->default = $default;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value, string $context): array
    {
        if (!is_array($value)) {
            throw new InvalidConfiguration($context . ' must be an array of strings');
        }

        return array_values(array_map(
            static function (mixed $item) use ($context): string {
                if (!is_scalar($item)) {
                    throw new InvalidConfiguration($context . ' must be an array of strings');
                }

                return (string) $item;
            },
            $value
        ));
    }

    /**
     * @return array<string, string>
     */
    private function stringMap(mixed $value, string $context): array
    {
        if (!is_array($value)) {
            throw new InvalidConfiguration($context . ' must be a map of strings');
        }

        $map = [];
        foreach ($value as $key => $item) {
            if (!is_scalar($item)) {
                throw new InvalidConfiguration($context . ' must be a map of strings');
            }

            $map[(string) $key] = (string) $item;
        }

        return $map;
    }

    private function suiteNameFor(string $file): string
    {
        $name = basename($file);
        $name = (string) preg_replace('/^phpunit\.?/', '', $name);
        $name = (string) preg_replace('/\.?xml(\.dist)?$/', '', $name);

        return $name === '' ? 'unit' : $name;
    }
}
