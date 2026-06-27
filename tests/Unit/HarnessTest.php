<?php

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HarnessTest extends TestCase
{
    public function testAutoloaderAndRunnerAreWired(): void
    {
        $this->assertTrue(class_exists(TestCase::class));
    }
}
