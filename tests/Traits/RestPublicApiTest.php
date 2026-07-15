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

use Phalcon\Talon\Tests\Fakes\Rest\DefaultSeamConsumer;
use Phalcon\Talon\Tests\Fakes\Rest\PublicApiConsumer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\MockHttpClient;

final class RestPublicApiTest extends TestCase
{
    public function testActionsAreCallableFromOutsideTheHierarchy(): void
    {
        $subject = new PublicApiConsumer('restPublicApi');

        $subject->haveHttpHeader('X-Token', 'abc');
        $subject->unsetHttpHeader('X-Token');
        $subject->amBearerAuthenticated('tok');
        $subject->amHttpAuthenticated('sarah', 'secret');
        $subject->startFollowingRedirects();
        $subject->stopFollowingRedirects();

        $subject->send('GET', '/x');
        $subject->sendGet('/companies');
        $subject->sendPost('/companies', ['name' => 'Acme']);
        $subject->sendPut('/companies/1', ['name' => 'Acme']);
        $subject->sendPatch('/companies/1', ['name' => 'Acme']);
        $subject->sendDelete('/companies/1');
        $subject->sendHead('/companies');
        $subject->sendOptions('/companies');

        $this->assertSame(200, $subject->grabResponseCode());
        $this->assertSame('application/json', $subject->grabHttpHeader('Content-Type'));
        $this->assertSame(PublicApiConsumer::BODY, $subject->grabResponse());
    }

    public function testAssertionsAreCallableFromOutsideTheHierarchy(): void
    {
        $subject = new PublicApiConsumer('restPublicApi');
        $subject->sendGet('/companies');

        $subject->assertResponseCodeIs(200);
        $subject->assertResponseCodeIsNot(404);
        $subject->assertResponseCodeIsSuccessful();
        $subject->assertResponseIsJson();
        $subject->assertResponseEquals(PublicApiConsumer::BODY);
        $subject->assertResponseContains('Acme');
        $subject->assertResponseNotContains('Nope');
        $subject->assertResponseContainsJson(['data' => [['name' => 'Acme']]]);
        $subject->assertResponseNotContainsJson(['data' => [['name' => 'Nope']]]);
        $subject->assertResponseMatchesJsonType(['jsonapi' => ['version' => 'string']]);
        $subject->assertResponseNotMatchesJsonType(['jsonapi' => ['version' => 'integer']]);
        $subject->assertHttpHeader('Content-Type');
        $subject->assertHttpHeader('Content-Type', 'application/json');
        $subject->assertNoHttpHeader('X-Absent');
        $subject->assertNoHttpHeader('Content-Type', 'text/html');

        $this->assertSame(200, $subject->grabResponseCode());
    }

    /**
     * PublicApiConsumer overrides both seams, so it cannot show whether the
     * trait's own definitions stay reachable from a child class. This one does
     * not override them.
     */
    public function testDefaultSeamsStayReachableFromASubclass(): void
    {
        $subject = new DefaultSeamConsumer('restDefaultSeams');

        $this->assertSame('http://127.0.0.1:8080', $subject->rawBaseUrl());
        $this->assertNull($subject->rawHttpClient());

        $subject->useRestBaseUrl('http://injected.test:9000');

        $this->assertSame('http://injected.test:9000', $subject->rawBaseUrl());
    }

    public function testRangeAssertionsAreCallableFromOutsideTheHierarchy(): void
    {
        $subject = new PublicApiConsumer('restPublicApi');

        $subject->respondWith(302);
        $subject->sendGet('/x');
        $subject->assertResponseCodeIsRedirection();

        $subject->respondWith(404);
        $subject->sendGet('/x');
        $subject->assertResponseCodeIsClientError();

        $subject->respondWith(503);
        $subject->sendGet('/x');
        $subject->assertResponseCodeIsServerError();

        $this->assertSame(503, $subject->grabResponseCode());
    }

    public function testSeamsStaySubclassAccessible(): void
    {
        $subject = new PublicApiConsumer('restPublicApi');

        $this->assertSame('http://api.test:8080', $subject->rawBaseUrl());
        $this->assertInstanceOf(HttpBrowser::class, $subject->rawBrowser());
        $this->assertInstanceOf(MockHttpClient::class, $subject->rawHttpClient());
    }
}
