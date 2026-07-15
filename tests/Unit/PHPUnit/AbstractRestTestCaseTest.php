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

namespace Phalcon\Talon\Tests\Unit\PHPUnit;

use Phalcon\Talon\PHPUnit\AbstractRestTestCase;
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;
use Phalcon\Talon\Traits\RestAssertionsTrait;
use Phalcon\Talon\Traits\RestTrait;

use function class_parents;
use function class_uses;
use function in_array;

final class AbstractRestTestCaseTest extends AbstractUnitTestCase
{
    public function testExtendsAbstractUnitTestCase(): void
    {
        $this->assertContains(
            AbstractUnitTestCase::class,
            (array) class_parents(AbstractRestTestCase::class)
        );
    }

    public function testUsesBothRestTraits(): void
    {
        $uses = (array) class_uses(AbstractRestTestCase::class);

        $this->assertTrue(in_array(RestTrait::class, $uses, true));
        $this->assertTrue(in_array(RestAssertionsTrait::class, $uses, true));
    }
}
