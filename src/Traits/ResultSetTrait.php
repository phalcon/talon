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

use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Talon\Exceptions\InvalidResultsetClass;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use stdClass;

use function array_key_first;
use function array_key_last;
use function array_keys;
use function count;
use function is_a;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait ResultSetTrait
{
    /**
     * @param array<int|string, mixed> $dataSet
     * @param class-string             $className
     *
     * @return Resultset<int, ModelInterface<object>>&MockObject
     */
    public function mockResultSet(array $dataSet, string $className = Resultset::class): MockObject
    {
        if ($className !== Resultset::class && !is_a($className, Resultset::class, true)) {
            throw new InvalidResultsetClass($className);
        }

        $mock = $this->createMock($className);

        // count() and seek() are final; they read the protected count/rows properties.
        (new ReflectionProperty(Resultset::class, 'count'))->setValue($mock, count($dataSet));
        (new ReflectionProperty(Resultset::class, 'rows'))->setValue($mock, $dataSet);

        $cursor      = new stdClass();
        $cursor->pos = 0;

        $mock->method('getFirst')->willReturnCallback(
            static fn () => $dataSet === [] ? null : $dataSet[array_key_first($dataSet)]
        );
        $mock->method('getLast')->willReturnCallback(
            static fn () => $dataSet === [] ? null : $dataSet[array_key_last($dataSet)]
        );
        $mock->method('valid')->willReturnCallback(
            static fn (): bool => $cursor->pos < count($dataSet)
        );
        $mock->method('key')->willReturnCallback(
            static fn () => array_keys($dataSet)[$cursor->pos] ?? null
        );
        $mock->method('next')->willReturnCallback(
            static function () use ($cursor): void {
                $cursor->pos++;
            }
        );
        $mock->method('toArray')->willReturn($dataSet);

        return $mock;
    }
}
