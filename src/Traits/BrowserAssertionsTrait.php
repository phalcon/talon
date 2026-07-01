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

use Symfony\Component\DomCrawler\Crawler;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait BrowserAssertionsTrait
{
    public function assertPageContainsText(string $text): void
    {
        $this->assertStringContainsString($text, $this->crawler()->text());
    }

    public function assertPageMissingText(string $text): void
    {
        $this->assertStringNotContainsString($text, $this->crawler()->text());
    }

    abstract protected function crawler(): Crawler;
}
