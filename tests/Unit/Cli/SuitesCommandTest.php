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

namespace Phalcon\Talon\Tests\Unit\Cli;

use Phalcon\Talon\Cli\Command\SuitesCommand;
use Phalcon\Talon\Cli\SuiteMap;
use PHPUnit\Framework\TestCase;

use function dirname;
use function fopen;
use function rewind;
use function stream_get_contents;

final class SuitesCommandTest extends TestCase
{
    public function testListsSuitesWithAnnotations(): void
    {
        $stream = fopen('php://memory', 'w+');
        $this->assertNotFalse($stream);

        $map  = new SuiteMap(dirname(__DIR__, 2) . '/Fakes/Cli/configured');
        $code = (new SuitesCommand($map, $stream))->execute();

        rewind($stream);
        $output = (string) stream_get_contents($stream);

        $this->assertSame(0, $code);
        // Anchored lines: paths must be root-relative with no stray slash.
        $this->assertMatchesRegularExpression('#^unit\s+custom/unit\.xml$#m', $output);
        $this->assertMatchesRegularExpression(
            '#^db\s+custom/db\.xml \(missing\) \(default\)$#m',
            $output
        );
    }
}
