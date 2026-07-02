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
use Phalcon\Talon\Tests\Fakes\App\FakeAppWithNonDiContainer;
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

        $this->assertNull($client->getCookieJar()->get('malformed'));
        $this->assertNull($client->getCookieJar()->get('nonScalar'));
    }

    public function testCookieVariantsSurviveExtraction(): void
    {
        $client = new Client(static fn () => new FakeAppWithMalformedCookies());
        $client->request('GET', 'http://localhost/');

        $jar = $client->getCookieJar();

        // Kills continue->break on the malformed-cookie guards and the
        // rawurlencode cast: the int-valued cookie sits after them.
        $answer = $jar->get('answer');
        $this->assertNotNull($answer);
        $this->assertSame('42', $answer->getValue());

        // A path-scoped cookie is bucketed under its own path, not '/'.
        $this->assertNull($jar->get('scoped'));
        $scoped = $jar->get('scoped', '/sub');
        $this->assertNotNull($scoped);
        $this->assertSame('v', $scoped->getValue());

        // Expiration 0 must produce a session cookie without an Expires attribute.
        $sess = $jar->get('sess');
        $this->assertNotNull($sess);
        $this->assertNull($sess->getExpiresTime());
    }

    public function testMaxRedirectsIsCapped(): void
    {
        $this->assertSame(20, $this->client()->getMaxRedirects());
    }

    public function testNonDiContainerSkipsCookieExtraction(): void
    {
        $client = new Client(static fn () => new FakeAppWithNonDiContainer());
        $client->request('GET', 'http://localhost/');

        $this->assertSame([], $client->getCookieJar()->all());
    }

    public function testQueryStringReachesTheApp(): void
    {
        $client  = $this->client();
        $crawler = $client->request('GET', 'http://localhost/browser/query?q=needle');

        $this->assertStringContainsString('uri=/browser/query?q=needle|', $crawler->text());
        $this->assertStringContainsString('|got=needle', $crawler->text());
    }

    public function testRequestWithoutAPathDispatchesTheEmptyPath(): void
    {
        $client = new Client(static fn () => new FakeAppWithBareDi());
        $client->request('GET', 'http://localhost');

        $this->assertSame('', $client->getInternalResponse()->getContent());
    }

    public function testResponseDefaultsAreApplied(): void
    {
        $client = new Client(static fn () => new FakeAppWithBareDi());
        $client->request('GET', 'http://localhost/');

        $response = $client->getInternalResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
    }

    public function testSetCookieHeaderUsesGmtSuffixedExpires(): void
    {
        $client = $this->client();
        $client->request('GET', 'http://localhost/browser/cookieService');

        $header = $client->getInternalResponse()->getHeader('Set-Cookie');
        $this->assertIsString($header);
        $this->assertMatchesRegularExpression(
            '#^svc=value; Path=/; Expires=[A-Z][a-z]{2}, \d{2}-[A-Z][a-z]{2}-\d{4} \d{2}:\d{2}:\d{2} GMT$#',
            $header
        );
    }

    public function testSuperglobalsAreRestoredAfterDispatch(): void
    {
        $backup = $_GET;
        $_GET   = ['sentinel' => 'before'];

        try {
            $this->client()->request('GET', 'http://localhost/browser/landed');

            $this->assertSame(['sentinel' => 'before'], $_GET);
        } finally {
            $_GET = $backup;
        }
    }

    private function client(): Client
    {
        return new Client(static fn () => require __DIR__ . '/../../Fakes/Browser/app.php');
    }
}
