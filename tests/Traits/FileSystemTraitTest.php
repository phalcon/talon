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
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

use function chmod;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function uniqid;
use function unlink;

final class FileSystemTraitTest extends TestCase
{
    use FileSystemTrait;

    public function testDirSeparatorAndNewFileName(): void
    {
        $this->assertSame('/app/', $this->getDirSeparator('/app'));
        $this->assertStringEndsWith('.log', $this->getNewFileName('pre'));
    }

    public function testSafeDeleteDirectoryFailsWhenDeleteFails(): void
    {
        $dir = __DIR__ . '/../_output/locked-dir-' . uniqid();
        mkdir($dir . '/nested', 0o777, true);
        file_put_contents($dir . '/nested/undeletable.txt', 'x');
        chmod($dir . '/nested', 0o555);

        try {
            $this->expectException(AssertionFailedError::class);

            $this->safeDeleteDirectory($dir);
        } finally {
            chmod($dir . '/nested', 0o777);
            unlink($dir . '/nested/undeletable.txt');
            rmdir($dir . '/nested');
            rmdir($dir);
        }
    }

    public function testSafeDeleteDirectoryIgnoresMissing(): void
    {
        $this->safeDeleteDirectory(__DIR__ . '/../_output/missing-' . uniqid());

        $this->addToAssertionCount(1);
    }

    public function testSafeDeleteDirectoryRemovesRecursively(): void
    {
        $dir = __DIR__ . '/../_output/tree-' . uniqid();
        mkdir($dir . '/nested', 0o777, true);
        file_put_contents($dir . '/a.txt', 'x');
        file_put_contents($dir . '/nested/b.txt', 'y');

        $this->safeDeleteDirectory($dir);

        $this->assertDirectoryDoesNotExist($dir);
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

    public function testSafeDeleteFileFailsWhenUnlinkFails(): void
    {
        $dir  = __DIR__ . '/../_output/locked-' . uniqid();
        $file = $dir . '/undeletable.txt';
        mkdir($dir, 0o777, true);
        file_put_contents($file, 'x');
        chmod($dir, 0o555);

        try {
            $this->expectException(AssertionFailedError::class);

            $this->safeDeleteFile($file);
        } finally {
            chmod($dir, 0o777);
            unlink($file);
            rmdir($dir);
        }
    }
}
