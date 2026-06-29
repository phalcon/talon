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

namespace Phalcon\Talon\Tests\Unit\Exceptions;

use Phalcon\Talon\Exceptions\ElementNotFound;
use Phalcon\Talon\Exceptions\Exception;
use PHPUnit\Framework\TestCase;

final class ElementNotFoundTest extends TestCase
{
    public function testMessageAndType(): void
    {
        $exception = new ElementNotFound('link "Save"');

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('Could not find link "Save" on the page', $exception->getMessage());
    }
}
