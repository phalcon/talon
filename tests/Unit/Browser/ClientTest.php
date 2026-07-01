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

namespace Phalcon\Talon\Tests\Unit\Browser;

use Phalcon\Talon\Browser\Client;
use Phalcon\Talon\Exceptions\InvalidApplication;
use Phalcon\Talon\Tests\Fakes\App\FakeAppWithBareDi;
use Phalcon\Talon\Tests\Fakes\App\FakeAppWithMalformedCookies;
use Phalcon\Talon\Tests\Fakes\App\FakeAppWithNonCookiesService;
use Phalcon\Talon\Tests\Fakes\App\FakeAppWithoutGetDi;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\BrowserKit\Exception\LogicException;

final class ClientTest extends TestCase
{
    public function testGetRendersContent(): void
    {
        $client  = $this->client();
        $crawler = $client->request('GET', 'http://localhost/browser/form');

        $this->assertStringContainsString('<form', $crawler->html());
    }

    public function testPostEchoesParameters(): void
    {
        $client  = $this->client();
        $crawler = $client->request('POST', 'http://localhost/browser/echo', ['q' => 'hi']);

        $this->assertStringContainsString('post:hi', $crawler->text());
    }

    public function testRedirectIsFollowed(): void
    {
        $client  = $this->client();
        $crawler = $client->request('GET', 'http://localhost/browser/bounce');

        $this->assertStringContainsString('landed ok', $crawler->text());
    }

    public function testSetCookieHeaderLandsInTheJar(): void
    {
        $client = $this->client();
        $client->request('GET', 'http://localhost/browser/cookie');

        $cookie = $client->getCookieJar()->get('baked');
        $this->assertNotNull($cookie);
        $this->assertSame('yummy', $cookie->getValue());
    }

    public function testCookiesServiceCookiesLandInTheJar(): void
    {
        $client = $this->client();
        $client->request('GET', 'http://localhost/browser/cookieService');

        $cookie = $client->getCookieJar()->get('svc');
        $this->assertNotNull($cookie);
        $this->assertSame('value', $cookie->getValue());
    }

    public function testRedirectLoopRaisesInsteadOfRecursing(): void
    {
        $client = $this->client();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('maximum number');

        $client->request('GET', 'http://localhost/browser/loop');
    }

    public function testFactoryWithoutHandleThrows(): void
    {
        $client = new Client(static fn () => new stdClass());

        $this->expectException(InvalidApplication::class);

        $client->request('GET', 'http://localhost/');
    }

    public function testAppWithoutGetDiSkipsCookieExtraction(): void
    {
        $client = new Client(static fn () => new FakeAppWithoutGetDi());
        $client->request('GET', 'http://localhost/');

        $this->assertSame([], $client->getCookieJar()->all());
    }

    public function testBareDiWithoutCookiesServiceSkipsCookieExtraction(): void
    {
        $client = new Client(static fn () => new FakeAppWithBareDi());
        $client->request('GET', 'http://localhost/');

        $this->assertSame([], $client->getCookieJar()->all());
    }

    public function testNonCookiesServiceSkipsCookieExtraction(): void
    {
        $client = new Client(static fn () => new FakeAppWithNonCookiesService());
        $client->request('GET', 'http://localhost/');

        $this->assertSame([], $client->getCookieJar()->all());
    }

    public function testMalformedCookiesAreSkipped(): void
    {
        $client = new Client(static fn () => new FakeAppWithMalformedCookies());
        $client->request('GET', 'http://localhost/');

        $this->assertSame([], $client->getCookieJar()->all());
    }

    private function client(): Client
    {
        return new Client(static fn () => require __DIR__ . '/../../Fakes/Browser/app.php');
    }
}
