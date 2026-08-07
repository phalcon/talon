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

namespace Phalcon\Talon\Tests\Fixtures\NotSchemas;

/**
 * Not a SchemaDefinition. The collector must skip it rather than fail on it,
 * so a schema directory can hold helpers alongside the fixtures.
 */
final class PlainClass
{
    public function getTable(): string
    {
        return 'not_a_schema';
    }
}
