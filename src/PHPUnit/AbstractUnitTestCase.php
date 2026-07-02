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

namespace Phalcon\Talon\PHPUnit;

use Closure;
use Phalcon\Di\Di;
use Phalcon\Talon\Environment;
use Phalcon\Talon\Traits\FileSystemTrait;
use Phalcon\Talon\Traits\ReflectionTrait;
use PHPUnit\Framework\MockObject\Generator\Generator;
use PHPUnit\Framework\SkippedTestSuiteError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;
use ReflectionClass;

use function array_values;
use function extension_loaded;
use function method_exists;
use function sprintf;
use function version_compare;

abstract class AbstractUnitTestCase extends TestCase
{
    use ReflectionTrait;
    use FileSystemTrait;

    protected function setUp(): void
    {
        parent::setUp();

        Di::reset();
    }

    public function checkExtensionIsLoaded(string $extension): void
    {
        if (!extension_loaded($extension)) {
            throw new SkippedTestSuiteError(
                sprintf("Extension '%s' is not loaded. Skipping test", $extension)
            );
        }
    }

    public function checkPhalconAvailable(): void
    {
        if (!$this->phalconAvailable()) {
            throw new SkippedTestSuiteError(
                'Phalcon is not available (ext-phalcon or phalcon/phalcon). Skipping test'
            );
        }
    }

    /**
     * Build a mock of the class with its constructor invoked using the given arguments.
     *
     * Method overrides are applied as stubs, so the calls the constructor makes to those
     * methods are neutralized while the constructor body itself still runs.
     *
     * @template T of object
     *
     * @param class-string<T>          $class
     * @param array<array-key, mixed>  $ctorArgs
     * @param array<string, mixed>     $overrides
     *
     * @return T
     */
    public function mockWithConstructor(string $class, array $ctorArgs = [], array $overrides = []): object
    {
        /** @var T $mock */
        $mock = $this->buildMock($class, $overrides, true, $ctorArgs);

        return $mock;
    }

    /**
     * Build a mock of the class without invoking its constructor.
     *
     * Keys in $overrides that name a method are stubbed - a Closure becomes the method
     * body, any other non-null value becomes the return value, and null leaves PHPUnit's
     * type-compatible default. Any other key is treated as a property and set via reflection.
     *
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $overrides
     *
     * @return T
     */
    public function mockWithoutConstructor(string $class, array $overrides = []): object
    {
        /** @var T $mock */
        $mock = $this->buildMock($class, $overrides, false, []);

        return $mock;
    }

    protected function phalconAvailable(): bool
    {
        return Environment::phalconAvailable();
    }

    /**
     * @param class-string            $class
     * @param array<string, mixed>    $overrides
     * @param array<array-key, mixed> $ctorArgs
     */
    private function buildMock(string $class, array $overrides, bool $withConstructor, array $ctorArgs): object
    {
        $methodNames       = [];
        $methodOverrides   = [];
        $propertyOverrides = [];

        foreach ($overrides as $name => $value) {
            if ('' !== $name && method_exists($class, $name)) {
                $methodNames[]          = $name;
                $methodOverrides[$name] = $value;

                continue;
            }

            $propertyOverrides[$name] = $value;
        }

        if ([] === $methodNames) {
            $object = $withConstructor
                ? new $class(...array_values($ctorArgs))
                : (new ReflectionClass($class))->newInstanceWithoutConstructor();
        } else {
            // Build the double via the low-level generator as a stub (PHPUnit 12+) or a
            // configurable mock (< 12). A stub avoids the "mock object without expectations"
            // notice PHPUnit 12+ raises, while $methodNames keeps the other methods real.
            $object = (new Generator())->testDouble(
                $class,
                version_compare(Version::series(), '12', '<'),
                $methodNames,
                array_values($ctorArgs),
                '',
                $withConstructor,
            );

            foreach ($methodOverrides as $name => $value) {
                if ($value instanceof Closure) {
                    $object->method($name)->willReturnCallback($value);

                    continue;
                }

                if (null !== $value) {
                    $object->method($name)->willReturn($value);
                }
            }
        }

        foreach ($propertyOverrides as $name => $value) {
            $this->setProtectedProperty($object, $name, $value);
        }

        return $object;
    }
}
