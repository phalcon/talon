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

namespace Phalcon\Talon\Tests\Unit;

use Phalcon\Talon\ServiceOptions;
use PHPUnit\Framework\TestCase;

final class ServiceOptionsTest extends TestCase
{
    public function testGetNameReturnsConstructedName(): void
    {
        $options = new ServiceOptions('redis', ['host' => '127.0.0.1']);

        $this->assertSame('redis', $options->getName());
    }

    public function testGetOptionsReturnsConstructedOptions(): void
    {
        $options = new ServiceOptions('redis', ['host' => '127.0.0.1', 'port' => 6379]);

        $this->assertSame(['host' => '127.0.0.1', 'port' => 6379], $options->getOptions());
    }
}
