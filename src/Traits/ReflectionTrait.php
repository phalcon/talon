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

namespace Phalcon\Talon\Traits;

use ReflectionClass;

use function get_class;
use function is_object;

trait ReflectionTrait
{
    /**
     * @param class-string|object $obj
     */
    public function callProtectedMethod(string | object $obj, string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionClass($obj);
        $target     = is_object($obj) ? $obj : $reflection->newInstanceWithoutConstructor();

        return $reflection->getMethod($method)->invokeArgs($target, $args);
    }

    /**
     * @param class-string|object $obj
     */
    public function getProtectedProperty(object | string $obj, string $property): mixed
    {
        return (new ReflectionClass($obj))
            ->getProperty($property)
            ->getValue(is_object($obj) ? $obj : null);
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        return (new ReflectionClass(get_class($object)))
            ->getMethod($methodName)
            ->invokeArgs($object, $parameters);
    }

    /**
     * @param class-string|object $obj
     */
    public function setProtectedProperty(object | string $obj, string $property, mixed $value): void
    {
        $reflectionProperty = (new ReflectionClass($obj))->getProperty($property);

        if (is_object($obj)) {
            $reflectionProperty->setValue($obj, $value);

            return;
        }

        $reflectionProperty->setValue(null, $value);
    }
}
