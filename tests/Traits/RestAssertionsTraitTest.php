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

use Phalcon\Talon\PHPUnit\AbstractRestTestCase;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RestAssertionsTraitTest extends AbstractRestTestCase
{
    private string $body = '';

    /** @var array<string, string> */
    private array $headers = ['Content-Type' => 'application/json'];

    private int $status = 200;

    public function testAssertResponseCodeIs(): void
    {
        $this->respondWith('{}', 201);

        $this->assertResponseCodeIs(201);
        $this->assertResponseCodeIsNot(200);
    }

    public function testAssertResponseCodeIsFails(): void
    {
        $this->respondWith('{}', 404);

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseCodeIs(200);
    }

    public function testAssertResponseContainsJsonMatchesFragment(): void
    {
        $this->respondWith('{"jsonapi":{"version":"1.0"},"data":[{"id":1,"name":"Acme"}]}');

        $this->assertResponseContainsJson(['data' => [['name' => 'Acme']]]);
        $this->assertResponseNotContainsJson(['data' => [['name' => 'Other']]]);
    }

    public function testAssertResponseIsJson(): void
    {
        $this->respondWith('{"a":1}');

        $this->assertResponseIsJson();
    }

    public function testAssertResponseIsJsonFailsOnGarbage(): void
    {
        $this->respondWith('not json');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseIsJson();
    }

    public function testAssertResponseMatchesJsonType(): void
    {
        $this->respondWith(
            '{"jsonapi":{"version":"1.0"},"meta":{"timestamp":"2026-07-15T10:30:00+00:00","hash":"abc"}}'
        );

        $this->assertResponseMatchesJsonType([
            'jsonapi' => ['version' => 'string'],
            'meta'    => [
                'timestamp' => 'string:date',
                'hash'      => 'string',
            ],
        ]);
    }

    public function testAssertResponseMatchesJsonTypeFailureNamesThePath(): void
    {
        $this->respondWith('{"meta":{"hash":1}}');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('/meta\.hash/');

        $this->assertResponseMatchesJsonType(['meta' => ['hash' => 'string']]);
    }

    public function testAssertResponseNotMatchesJsonType(): void
    {
        $this->respondWith('{"meta":{"hash":1}}');

        $this->assertResponseNotMatchesJsonType(['meta' => ['hash' => 'string']]);
    }

    public function testBodyAssertions(): void
    {
        $this->respondWith('{"a":1}');

        $this->assertResponseEquals('{"a":1}');
        $this->assertResponseContains('"a"');
        $this->assertResponseNotContains('"b"');
    }

    public function testHeaderAssertions(): void
    {
        $this->respondWith('{}');

        $this->assertHttpHeader('Content-Type');
        $this->assertHttpHeader('Content-Type', 'application/json');
        $this->assertNoHttpHeader('X-Absent');
        $this->assertNoHttpHeader('Content-Type', 'text/html');
    }

    public function testJsonAssertionsTreatANonObjectResponseAsEmpty(): void
    {
        $this->respondWith('"just a string"');

        $this->assertResponseNotContainsJson(['a' => 1]);
    }

    public function testRangeAssertions(): void
    {
        $this->respondWith('{}', 204);
        $this->assertResponseCodeIsSuccessful();

        $this->respondWith('{}', 302);
        $this->assertResponseCodeIsRedirection();

        $this->respondWith('{}', 404);
        $this->assertResponseCodeIsClientError();

        $this->respondWith('{}', 503);
        $this->assertResponseCodeIsServerError();
    }

    protected function restBaseUrl(): string
    {
        return 'http://api.test:8080';
    }

    protected function restHttpClient(): HttpClientInterface
    {
        return new MockHttpClient(fn (): MockResponse => new MockResponse(
            $this->body,
            [
                'http_code'        => $this->status,
                'response_headers' => $this->headers,
            ]
        ));
    }

    private function respondWith(string $body, int $status = 200): void
    {
        $this->body   = $body;
        $this->status = $status;

        $this->sendGet('/x');
    }
}
