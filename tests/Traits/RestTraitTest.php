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

use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;
use Phalcon\Talon\Traits\RestTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_shift;
use function base64_encode;
use function is_string;
use function json_decode;

final class RestTraitTest extends AbstractUnitTestCase
{
    use RestTrait;

    /**
     * @var array<int, array{method: string, url: string, headers: array<int, string>, body: string}>
     */
    private array $requests = [];

    /** @var array<int, MockResponse> */
    private array $responses = [];

    public function testAbsoluteHttpsUrlIsNotPrefixed(): void
    {
        $this->sendGet('https://secure.test/ping');

        $this->assertSame('https://secure.test/ping', $this->requests[0]['url']);
    }

    public function testAbsoluteUrlIsNotPrefixed(): void
    {
        $this->sendGet('http://other.test/ping');

        $this->assertSame('http://other.test/ping', $this->requests[0]['url']);
    }

    public function testAmBearerAuthenticatedSetsAuthorizationHeader(): void
    {
        $this->amBearerAuthenticated('tok123');
        $this->sendGet('/secure');

        $this->assertContains('authorization: Bearer tok123', $this->requests[0]['headers']);
    }

    public function testAmHttpAuthenticatedSetsBasicHeader(): void
    {
        $this->amHttpAuthenticated('sarah', 'secret');
        $this->sendGet('/secure');

        $this->assertContains(
            'authorization: Basic ' . base64_encode('sarah:secret'),
            $this->requests[0]['headers']
        );
    }

    public function testGrabHttpHeader(): void
    {
        $this->sendGet('/companies');

        $this->assertSame('application/json', $this->grabHttpHeader('Content-Type'));
        $this->assertNull($this->grabHttpHeader('X-Absent'));
    }

    public function testGrabResponseAndCode(): void
    {
        $this->sendGet('/companies');

        $this->assertSame('{"data":{"id":1}}', $this->grabResponse());
        $this->assertSame(201, $this->grabResponseCode());
    }

    public function testGrabResponseBeforeSendingThrows(): void
    {
        $this->expectException(ResponseNotDispatched::class);

        $this->grabResponse();
    }

    public function testHeaderPersistsAcrossRequests(): void
    {
        $this->haveHttpHeader('X-Token', 'abc');
        $this->sendGet('/one');
        $this->sendGet('/two');

        $this->assertContains('x-token: abc', $this->requests[0]['headers']);
        $this->assertContains('x-token: abc', $this->requests[1]['headers']);
    }

    public function testQueryAppendedWithAmpersandWhenUrlHasQuery(): void
    {
        $this->sendGet('/companies?filter=x', ['page' => 2]);

        $this->assertSame('http://api.test:8080/companies?filter=x&page=2', $this->requests[0]['url']);
    }

    public function testSendGetAppendsQueryParameters(): void
    {
        $this->sendGet('/companies', ['page' => 2]);

        $this->assertSame('http://api.test:8080/companies?page=2', $this->requests[0]['url']);
    }

    public function testSendGetBuildsAbsoluteUrlFromBase(): void
    {
        $this->sendGet('/companies');

        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame('http://api.test:8080/companies', $this->requests[0]['url']);
    }

    public function testSendPostAcceptsRawStringBody(): void
    {
        $this->sendPost('/login', '{"raw":true}');

        $this->assertSame('{"raw":true}', $this->requests[0]['body']);
    }

    public function testSendPostSendsFormParametersByDefault(): void
    {
        $this->sendPost('/login', ['username' => 'sarah']);

        $this->assertSame('POST', $this->requests[0]['method']);
        $this->assertStringContainsString('username', $this->requests[0]['body']);
        $this->assertStringContainsString('sarah', $this->requests[0]['body']);
    }

    public function testSendPostSerializesJsonWhenContentTypeIsJson(): void
    {
        $this->haveHttpHeader('Content-Type', 'application/json');
        $this->sendPost('/login', ['username' => 'sarah']);

        $this->assertSame(
            ['username' => 'sarah'],
            json_decode($this->requests[0]['body'], true)
        );
    }

    public function testStartFollowingRedirectsFollowsLocation(): void
    {
        $this->responses = [
            new MockResponse('', [
                'http_code'        => 302,
                'response_headers' => ['Location' => 'http://api.test:8080/target'],
            ]),
            new MockResponse('done', ['http_code' => 200]),
        ];

        $this->startFollowingRedirects();
        $this->sendGet('/start');

        $this->assertCount(2, $this->requests);
        $this->assertSame('http://api.test:8080/target', $this->requests[1]['url']);
        $this->assertSame('done', $this->grabResponse());
    }

    public function testStopFollowingRedirectsDoesNotFollow(): void
    {
        $this->responses = [
            new MockResponse('', [
                'http_code'        => 302,
                'response_headers' => ['Location' => 'http://api.test:8080/target'],
            ]),
        ];

        $this->stopFollowingRedirects();
        $this->sendGet('/start');

        $this->assertCount(1, $this->requests);
        $this->assertSame(302, $this->grabResponseCode());
    }

    public function testUnsetHttpHeaderRemovesIt(): void
    {
        $this->haveHttpHeader('X-Token', 'abc');
        $this->sendGet('/one');
        $this->unsetHttpHeader('X-Token');
        $this->sendGet('/two');

        $this->assertContains('x-token: abc', $this->requests[0]['headers']);
        $this->assertNotContains('x-token: abc', $this->requests[1]['headers']);
    }

    public function testVerbsDispatchTheRightMethod(): void
    {
        $this->sendPut('/a');
        $this->sendPatch('/b');
        $this->sendDelete('/c');
        $this->sendHead('/d');
        $this->sendOptions('/e');

        $this->assertSame('PUT', $this->requests[0]['method']);
        $this->assertSame('PATCH', $this->requests[1]['method']);
        $this->assertSame('DELETE', $this->requests[2]['method']);
        $this->assertSame('HEAD', $this->requests[3]['method']);
        $this->assertSame('OPTIONS', $this->requests[4]['method']);
    }

    protected function restBaseUrl(): string
    {
        return 'http://api.test:8080';
    }

    protected function restHttpClient(): HttpClientInterface
    {
        return new MockHttpClient(
            /**
             * @param array<string, mixed> $options
             */
            function (string $method, string $url, array $options): MockResponse {
                /** @var array<int, string> $headers */
                $headers = $options['headers'] ?? [];
                $body    = $options['body'] ?? '';

                $this->requests[] = [
                    'method'  => $method,
                    'url'     => $url,
                    'headers' => $headers,
                    'body'    => is_string($body) ? $body : '',
                ];

                return array_shift($this->responses) ?? new MockResponse(
                    '{"data":{"id":1}}',
                    [
                        'http_code'        => 201,
                        'response_headers' => ['Content-Type' => 'application/json'],
                    ]
                );
            }
        );
    }
}
