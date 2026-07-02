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

namespace Phalcon\Talon\Tests\Fakes;

use Phalcon\Talon\Traits\FunctionalAssertionsTrait;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\Assert;

/**
 * Uses the functional traits in a plain Assert subclass so tests can verify
 * the traits' method visibility from external and subclass scopes.
 */
class FunctionalFixture extends Assert
{
    use FunctionalAssertionsTrait;
    use FunctionalTrait;

    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/App/app.php';
    }
}
