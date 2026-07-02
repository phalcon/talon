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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

use function chmod;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function symlink;
use function uniqid;
use function unlink;

final class FileSystemTraitTest extends TestCase
{
    use FileSystemTrait;

    public function testAssertFileContentsContainsFailsWhenFileMissing(): void
    {
        $this->expectException(AssertionFailedError::class);

        @$this->assertFileContentsContains(
            __DIR__ . '/../_output/missing-' . uniqid() . '.txt',
            'needle'
        );
    }

    public function testAssertFileContentsEqualFailsOnMismatch(): void
    {
        $dir  = __DIR__ . '/../_output';
        $file = $this->getDirSeparator($dir) . $this->getNewFileName('mismatch', 'txt');
        file_put_contents($file, 'hello');

        try {
            $this->expectException(AssertionFailedError::class);

            $this->assertFileContentsEqual($file, 'other');
        } finally {
            unlink($file);
        }
    }

    public function testAssertFileContentsEqualTreatsMissingFileAsEmptyString(): void
    {
        @$this->assertFileContentsEqual(
            __DIR__ . '/../_output/missing-' . uniqid() . '.txt',
            ''
        );
    }

    public function testDirSeparatorAndNewFileName(): void
    {
        $this->assertSame('/app/', $this->getDirSeparator('/app'));
        $this->assertSame('/app/', $this->getDirSeparator('/app/'));

        $name = $this->getNewFileName('pre', 'txt');
        $this->assertStringStartsWith('pre_', $name);
        $this->assertStringEndsWith('.txt', $name);

        $this->assertStringEndsWith('.log', $this->getNewFileName('pre'));
        $this->assertStringEndsWith('.log', $this->getNewFileName('pre', ''));
    }

    public function testPublicApiIsCallableFromOutside(): void
    {
        $fixture = new class () extends Assert {
            use FileSystemTrait;
        };

        $dir  = $fixture->getDirSeparator(__DIR__ . '/../_output');
        $file = $dir . $fixture->getNewFileName('visibility', 'txt');
        file_put_contents($file, 'visibility check');

        $fixture->assertFileContentsContains($file, 'check');
        $fixture->assertFileContentsEqual($file, 'visibility check');
        $fixture->safeDeleteFile($file);

        $tree = $dir . 'visibility-tree-' . uniqid();
        mkdir($tree, 0o777, true);
        $fixture->safeDeleteDirectory($tree);

        $this->assertFileDoesNotExist($file);
        $this->assertDirectoryDoesNotExist($tree);
    }

    public function testSafeDeleteDirectoryFailsOnBrokenSymlink(): void
    {
        $dir = __DIR__ . '/../_output/symlink-dir-' . uniqid();
        mkdir($dir, 0o777, true);
        symlink($dir . '/missing-target', $dir . '/dangling');

        try {
            $this->expectException(AssertionFailedError::class);
            $this->expectExceptionMessage("Failed to delete the file ''");

            $this->safeDeleteDirectory($dir);
        } finally {
            unlink($dir . '/dangling');
            rmdir($dir);
        }
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

    public function testSafeDeleteDirectoryRemovesNestedChain(): void
    {
        $dir = __DIR__ . '/../_output/chain-' . uniqid();
        mkdir($dir . '/sub1/sub2', 0o777, true);
        file_put_contents($dir . '/sub1/sub2/file.txt', 'x');

        $this->safeDeleteDirectory($dir);

        $this->assertDirectoryDoesNotExist($dir);
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

    public function testSafeDeleteFileIgnoresDirectory(): void
    {
        $dir = __DIR__ . '/../_output/dir-not-file-' . uniqid();
        mkdir($dir, 0o777, true);

        try {
            $this->safeDeleteFile($dir);

            $this->assertDirectoryExists($dir);
        } finally {
            rmdir($dir);
        }
    }
}
