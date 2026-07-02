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

namespace Phalcon\Talon\Tests\Fakes\Browser;

use Phalcon\Talon\Browser\Client;
use Phalcon\Talon\PHPUnit\AbstractBrowserTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Exposes the browser traits to a scope outside the AbstractBrowserTestCase
 * hierarchy, so tests can verify the public API stays publicly callable and
 * the protected browser()/crawler() seams stay subclass-accessible.
 */
final class PublicApiConsumer extends AbstractBrowserTestCase
{
    public function rawBrowser(): Client
    {
        return $this->browser();
    }

    public function rawCrawler(): Crawler
    {
        return $this->crawler();
    }

    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/app.php';
    }
}
