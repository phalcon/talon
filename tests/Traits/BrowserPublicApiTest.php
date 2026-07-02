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

use Phalcon\Talon\Browser\Client;
use Phalcon\Talon\Tests\Fakes\Browser\PublicApiConsumer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

final class BrowserPublicApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    public function testBrowserApiIsCallableFromOutsideTheHierarchy(): void
    {
        $subject = new PublicApiConsumer('browserPublicApi');

        $subject->visitPage('/browser/form');
        $subject->assertPageContainsText('Log In');
        $subject->assertPageMissingText('Welcome');
        $subject->fillField('name', 'sarah');
        $subject->selectOption('active', 'Yes');
        $subject->pressButton('Log In');
        $subject->assertPageContainsText('Welcome sarah');

        $subject->setCookie('talon', 'crunchy');
        $this->assertSame('crunchy', $subject->getCookie('talon'));

        $subject->visitPage('/browser/menu');
        $subject->clickLink('Go');
        $subject->assertPageContainsText('landed ok');

        $this->assertInstanceOf(Client::class, $subject->rawBrowser());
        $this->assertInstanceOf(Crawler::class, $subject->rawCrawler());
    }
}
