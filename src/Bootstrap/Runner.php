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

use function date_default_timezone_set;
use function error_reporting;
use function is_dir;
use function mkdir;

use const E_ALL;

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
        $output = $this->settings->path('tests/_output');

        if (!is_dir($output)) {
            mkdir($output, 0o777, true);
        }
    }

    protected function initEnvironment(): void
    {
        error_reporting(E_ALL);
        date_default_timezone_set('UTC');
    }

    protected function initSettings(): void
    {
        Talon::useSettings($this->settings);
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
