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

namespace Phalcon\Talon\Bootstrap;

use Phalcon\Talon\Contracts\Bootstrap as BootstrapContract;
use Phalcon\Talon\Contracts\Settings;
use Phalcon\Talon\Talon;

use function clearstatcache;
use function date_default_timezone_set;
use function error_reporting;
use function extension_loaded;
use function function_exists;
use function ini_set;
use function is_dir;
use function mb_internal_encoding;
use function mkdir;
use function setlocale;

use const E_ALL;
use const LC_ALL;

class Runner implements BootstrapContract
{
    /** @var array<string, list<callable>> */
    private array $after = [];

    /** @var array<string, list<callable>> */
    private array $before = [];

    public function __construct(protected Settings $settings)
    {
    }

    public static function for(Settings $settings): self
    {
        return new self($settings);
    }

    public function after(Stage $stage, callable $hook): self
    {
        $this->after[$stage->name][] = $hook;

        return $this;
    }

    public function before(Stage $stage, callable $hook): self
    {
        $this->before[$stage->name][] = $hook;

        return $this;
    }

    public function boot(): Settings
    {
        $this->stage(Stage::Environment, fn () => $this->initEnvironment());
        $this->stage(Stage::Directories, fn () => $this->initDirectories());
        $this->stage(Stage::Settings, fn () => $this->initSettings());

        return $this->settings;
    }

    protected function initDirectories(): void
    {
        $output = $this->settings->outputPath();

        if (!is_dir($output)) {
            mkdir($output, 0o777, true);
        }
    }

    protected function initEnvironment(): void
    {
        error_reporting(E_ALL);
        date_default_timezone_set('UTC');

        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');

        setlocale(LC_ALL, 'en_US.utf-8');

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('utf-8');
        }

        clearstatcache();

        if ($this->isExtensionLoaded('xdebug')) {
            ini_set('xdebug.cli_color', '1');
            ini_set('xdebug.dump_globals', 'On');
            ini_set('xdebug.show_local_vars', 'On');
            ini_set('xdebug.max_nesting_level', '100');
            ini_set('xdebug.var_display_max_depth', '4');
        }
    }

    protected function initSettings(): void
    {
        Talon::useSettings($this->settings);
    }

    protected function isExtensionLoaded(string $extension): bool
    {
        return extension_loaded($extension);
    }

    private function stage(Stage $stage, callable $default): void
    {
        foreach ($this->before[$stage->name] ?? [] as $hook) {
            $hook($this->settings);
        }

        $default();

        foreach ($this->after[$stage->name] ?? [] as $hook) {
            $hook($this->settings);
        }
    }
}
