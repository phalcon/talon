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

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_exists;
use function file_get_contents;
use function is_dir;
use function is_file;
use function rmdir;
use function rtrim;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait FileSystemTrait
{
    public function assertFileContentsContains(string $fileName, string $stream): void
    {
        $this->assertStringContainsString($stream, (string) file_get_contents($fileName));
    }

    public function assertFileContentsEqual(string $fileName, string $stream): void
    {
        $this->assertSame($stream, (string) file_get_contents($fileName));
    }

    public function getDirSeparator(string $directory): string
    {
        return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function getNewFileName(string $prefix = '', string $suffix = 'log'): string
    {
        $prefix = $prefix !== '' ? $prefix . '_' : '';
        $suffix = $suffix !== '' ? $suffix : 'log';

        return uniqid($prefix, true) . '.' . $suffix;
    }

    public function safeDeleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            $path = (string) $fileInfo->getRealPath();
            if ($fileInfo->isDir()) {
                $this->assertTrue(
                    @rmdir($path),
                    "Failed to delete the directory '{$path}'"
                );

                continue;
            }
            $this->assertTrue(
                @unlink($path),
                "Failed to delete the file '{$path}'"
            );
        }

        $this->assertTrue(
            @rmdir($directory),
            "Failed to delete the directory '{$directory}'"
        );
    }

    public function safeDeleteFile(string $filename): void
    {
        if (file_exists($filename) && is_file($filename)) {
            $this->assertTrue(
                @unlink($filename),
                "Failed to delete the file '{$filename}'"
            );
        }
    }
}
