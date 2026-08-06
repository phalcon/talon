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

namespace Phalcon\Talon\Cli\Command;

use Phalcon\Talon\Contracts\Settings;
use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\Database\Schema\SchemaCollector;
use Phalcon\Talon\Database\Schema\SchemaGenerator;
use Phalcon\Talon\Database\Schema\SchemaWriter;
use Phalcon\Talon\Exceptions\UnknownDriver;

use function fwrite;
use function is_string;

use const PHP_EOL;
use const STDOUT;

/**
 * Emits one flat SQL dump per dialect from the project's schema definitions.
 */
final class SchemaCommand
{
    /**
     * @param resource $stdout
     */
    public function __construct(
        private readonly Settings $settings,
        private $stdout = STDOUT,
    ) {
    }

    /**
     * @param list<string> $arguments driver names; empty means every dialect
     */
    public function execute(array $arguments = []): int
    {
        $dialects = $this->dialects($arguments);

        $collector = new SchemaCollector(
            $this->settings->rootPath($this->setting('schema_source')),
            $this->setting('schema_namespace'),
            $this->setting('schema_pre'),
            $this->setting('schema_post'),
        );

        $writer = new SchemaWriter($collector, new SchemaGenerator($collector));
        $output = $this->settings->rootPath($this->setting('schema_output'));

        foreach ($dialects as $dialect) {
            fwrite($this->stdout, 'Wrote ' . $writer->write($dialect, $output) . PHP_EOL);
        }

        return 0;
    }

    /**
     * @param list<string> $arguments
     *
     * @return list<Dialect>
     */
    private function dialects(array $arguments): array
    {
        if ($arguments === []) {
            return Dialect::cases();
        }

        $dialects = [];
        foreach ($arguments as $name) {
            $dialects[] = Dialect::tryFrom($name) ?? throw new UnknownDriver($name);
        }

        return $dialects;
    }

    private function setting(string $key): string
    {
        $value = $this->settings->get($key, '');

        return is_string($value) ? $value : '';
    }
}
