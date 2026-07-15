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

use Closure;
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
use function stripos;

final class RestTraitTest extends AbstractUnitTestCase
{
    use RestTrait;

    /**
     * Uploads use a fixture rather than __FILE__: a multipart body embeds the
     * file's contents, so uploading the test itself makes it "contain" every
     * string this file asserts on, and the assertions pass whatever was sent.
     */
    private const FIXTURE = __DIR__ . '/../Fakes/Rest/upload.txt';

    /**
     * @var array<int, array{method: string, url: string, headers: array<int, string>, body: string}>
     */
    private array $requests = [];

    /** @var array<int, MockResponse> */
    private array $responses = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->useRestBaseUrl('http://api.test:8080');
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function providerBodylessVerbs(): array
    {
        return [
            ['DELETE'],
            ['GET'],
            ['HEAD'],
            ['OPTIONS'],
        ];
    }

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

    /**
     * A trailing slash on the configured base URL must not produce '//' once
     * the path is joined on.
     */
    public function testBaseUrlTrailingSlashIsNotDoubled(): void
    {
        $this->useRestBaseUrl('http://api.test:8080/');
        $this->sendGet('/companies');

        $this->assertSame('http://api.test:8080/companies', $this->requests[0]['url']);
    }

    /**
     * GET/HEAD/OPTIONS/DELETE put their params in the query string; every other
     * verb puts them in the body. Asserting the query proves each verb is in
     * the bodyless set - a body-carrying verb would leave the URL bare.
     *
     * @dataProvider providerBodylessVerbs
     */
    public function testBodylessVerbsSendParamsAsQuery(string $method): void
    {
        $this->send($method, '/companies', ['page' => 2]);

        $this->assertSame($method, $this->requests[0]['method']);
        $this->assertSame('http://api.test:8080/companies?page=2', $this->requests[0]['url']);
        $this->assertSame('', $this->requests[0]['body']);
    }

    /**
     * A caller-set server parameter must survive header changes - rebuilding the
     * whole server bag used to wipe it.
     */
    public function testCallerServerParametersSurviveHeaderChanges(): void
    {
        $this->restBrowser()->setServerParameter('HTTP_X_CUSTOM', 'kept');
        $this->haveHttpHeader('X-Token', 'abc');
        $this->sendGet('/one');
        $this->unsetHttpHeader('X-Token');
        $this->sendGet('/two');

        $this->assertContains('x-custom: kept', $this->requests[0]['headers']);
        $this->assertContains('x-custom: kept', $this->requests[1]['headers']);
    }

    public function testContentTypeMatchIsCaseInsensitive(): void
    {
        $this->haveHttpHeader('Content-Type', 'APPLICATION/JSON');
        $this->sendPost('/login', ['username' => 'sarah']);

        $this->assertSame(
            ['username' => 'sarah'],
            json_decode($this->requests[0]['body'], true)
        );
    }

    public function testDoesNotFollowRedirectsByDefault(): void
    {
        $this->responses = [
            new MockResponse('', [
                'http_code'        => 302,
                'response_headers' => ['Location' => 'http://api.test:8080/target'],
            ]),
        ];

        $this->sendGet('/start');

        $this->assertCount(1, $this->requests);
        $this->assertSame(302, $this->grabResponseCode());
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

    /**
     * A header set once the browser already exists must still reach the wire -
     * restBrowser() only syncs on construction, so haveHttpHeader() has to push
     * it itself.
     */
    public function testHeaderSetAfterTheFirstRequestApplies(): void
    {
        $this->sendGet('/one');
        $this->haveHttpHeader('X-Late', 'v');
        $this->sendGet('/two');

        $this->assertNotContains('x-late: v', $this->requests[0]['headers']);
        $this->assertContains('x-late: v', $this->requests[1]['headers']);
    }

    public function testQueryAppendedWithAmpersandWhenUrlHasQuery(): void
    {
        $this->sendGet('/companies?filter=x', ['page' => 2]);

        $this->assertSame('http://api.test:8080/companies?filter=x&page=2', $this->requests[0]['url']);
    }

    public function testRawStringBodyKeepsACallerSuppliedContentType(): void
    {
        $this->haveHttpHeader('Content-Type', 'application/xml');
        $this->sendPost('/login', '<r/>');

        $this->assertContains('content-type: application/xml', $this->requests[0]['headers']);
        $this->assertNotContains('content-type: application/json', $this->requests[0]['headers']);
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

    /**
     * HttpBrowser would otherwise wrap an unlabelled string body in a TextPart
     * and send it as text/plain, which a JSON API rejects.
     */
    public function testSendPostAcceptsRawStringBodyAndNamesItJson(): void
    {
        $this->sendPost('/login', '{"raw":true}');

        $this->assertSame('{"raw":true}', $this->requests[0]['body']);
        $this->assertContains('content-type: application/json', $this->requests[0]['headers']);
    }

    public function testSendPostSendsFormParametersByDefault(): void
    {
        $this->sendPost('/login', ['username' => 'sarah']);

        $this->assertSame('POST', $this->requests[0]['method']);
        // Asserted as urlencoded rather than by field name: 'username' and
        // 'sarah' both appear in a JSON body too, so a looser check cannot tell
        // the form path from the JSON one.
        $this->assertSame('username=sarah', $this->requests[0]['body']);
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

    /**
     * A plain path is what a caller reaches for first, and BrowserKit's own
     * response to one is to send no files at all, silently - so both shapes
     * have to work.
     */
    public function testSendPostUploadsAFileGivenAPlainPath(): void
    {
        $this->sendPost('/upload', ['name' => 'Acme'], ['doc' => self::FIXTURE]);

        $this->assertSame('POST', $this->requests[0]['method']);
        $this->assertStringContainsString('multipart/form-data', $this->contentTypeOf(0));
        $this->assertStringContainsString('filename="upload.txt"', $this->requests[0]['body']);
        $this->assertStringContainsString('talon upload fixture', $this->requests[0]['body']);
        $this->assertStringContainsString('Acme', $this->requests[0]['body']);
    }

    public function testSendPostUploadsAFileGivenTheFilesShape(): void
    {
        $this->sendPost('/upload', [], ['doc' => ['tmp_name' => self::FIXTURE, 'name' => 'custom.txt']]);

        $this->assertStringContainsString('multipart/form-data', $this->contentTypeOf(0));
        $this->assertStringContainsString('filename="custom.txt"', $this->requests[0]['body']);
    }

    /**
     * More than one file, because a single-file test cannot notice a
     * normalisation that quietly keeps only the first.
     */
    public function testSendPostUploadsSeveralFiles(): void
    {
        $this->sendPost('/upload', [], [
            'first'  => self::FIXTURE,
            'second' => ['tmp_name' => self::FIXTURE, 'name' => 'second.txt'],
        ]);

        $this->assertStringContainsString('filename="upload.txt"', $this->requests[0]['body']);
        $this->assertStringContainsString('filename="second.txt"', $this->requests[0]['body']);
    }

    public function testSendUppercasesTheMethod(): void
    {
        $this->send('get', '/companies');

        $this->assertSame('GET', $this->requests[0]['method']);
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

    /**
     * stopFollowingRedirects() must actually turn following off, not merely
     * agree with the constructor default.
     */
    public function testStopFollowingRedirectsAfterStartingDoesNotFollow(): void
    {
        $this->responses = [
            new MockResponse('', [
                'http_code'        => 302,
                'response_headers' => ['Location' => 'http://api.test:8080/target'],
            ]),
        ];

        $this->startFollowingRedirects();
        $this->stopFollowingRedirects();
        $this->sendGet('/start');

        $this->assertCount(1, $this->requests);
        $this->assertSame(302, $this->grabResponseCode());
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

    public function testUseRestBaseUrlOverridesSettings(): void
    {
        $this->useRestBaseUrl('http://other.test:9000');
        $this->sendGet('/companies');

        $this->assertSame('http://other.test:9000/companies', $this->requests[0]['url']);
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

    protected function restHttpClient(): HttpClientInterface
    {
        return new MockHttpClient(
            /**
             * @param array<string, mixed> $options
             */
            function (string $method, string $url, array $options): MockResponse {
                /** @var array<int, string> $headers */
                $headers = $options['headers'] ?? [];

                $this->requests[] = [
                    'method'  => $method,
                    'url'     => $url,
                    'headers' => $headers,
                    'body'    => $this->readBody($options['body'] ?? ''),
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

    private function contentTypeOf(int $index): string
    {
        foreach ($this->requests[$index]['headers'] as $header) {
            if (stripos($header, 'content-type:') === 0) {
                return $header;
            }
        }

        return '';
    }

    /**
     * A multipart body arrives as the Closure Symfony normalizes an iterable
     * into, not a string; drain it so tests can assert on what was sent.
     */
    private function readBody(mixed $body): string
    {
        if (is_string($body)) {
            return $body;
        }

        if (!$body instanceof Closure) {
            return '';
        }

        $content = '';
        while (true) {
            $chunk = $body(16372);
            if (!is_string($chunk) || '' === $chunk) {
                break;
            }

            $content .= $chunk;
        }

        return $content;
    }
}
