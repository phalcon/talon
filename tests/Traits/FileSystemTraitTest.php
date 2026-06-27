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

namespace Phalcon\Talon\Tests\Traits;

use Phalcon\Talon\Traits\FileSystemTrait;
use PHPUnit\Framework\TestCase;

use function file_put_contents;

final class FileSystemTraitTest extends TestCase
{
    use FileSystemTrait;

    public function testDirSeparatorAndNewFileName(): void
    {
        $this->assertSame('/app/', $this->getDirSeparator('/app'));
        $this->assertStringEndsWith('.log', $this->getNewFileName('pre'));
    }

    public function testSafeDeleteFileAndContentsAssert(): void
    {
        $dir  = __DIR__ . '/../_output';
        $file = $this->getDirSeparator($dir) . $this->getNewFileName('talon', 'txt');

        file_put_contents($file, 'hello world');
        $this->assertFileContentsContains($file, 'world');
        $this->assertFileContentsEqual($file, 'hello world');

        $this->safeDeleteFile($file);
        $this->assertFileDoesNotExist($file);
    }
}
