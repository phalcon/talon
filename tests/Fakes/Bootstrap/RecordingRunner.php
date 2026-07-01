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

namespace Phalcon\Talon\Tests\Fakes\Bootstrap;

use ArrayObject;
use Phalcon\Talon\Bootstrap\Runner;
use Phalcon\Talon\Settings;

/**
 * Records the order each boot() stage runs in, instead of doing real
 * environment/directory/settings setup - drives Runner::boot()'s
 * before/after hook ordering assertions.
 */
final class RecordingRunner extends Runner
{
    /**
     * @param ArrayObject<int, string> $order
     */
    public function __construct(Settings $settings, private ArrayObject $order)
    {
        parent::__construct($settings);
    }

    protected function initDirectories(): void
    {
        $this->order->append('dirs');
    }

    protected function initEnvironment(): void
    {
        $this->order->append('env');
    }

    protected function initSettings(): void
    {
        $this->order->append('settings');
    }
}
