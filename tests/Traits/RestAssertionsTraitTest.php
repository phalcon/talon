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
    /**
     * Each range assertion is pinned at both bounds, one code inside and one
     * outside, so shifting either literal by one is observable.
     *
     * @return array<string, array{0: int, 1: string, 2: bool}>
     */
    public static function providerRangeBoundaries(): array
    {
        return [
            '199 not successful'   => [199, 'assertResponseCodeIsSuccessful', false],
            '200 successful'       => [200, 'assertResponseCodeIsSuccessful', true],
            '299 successful'       => [299, 'assertResponseCodeIsSuccessful', true],
            '300 not successful'   => [300, 'assertResponseCodeIsSuccessful', false],
            '299 not redirection'  => [299, 'assertResponseCodeIsRedirection', false],
            '300 redirection'      => [300, 'assertResponseCodeIsRedirection', true],
            '399 redirection'      => [399, 'assertResponseCodeIsRedirection', true],
            '400 not redirection'  => [400, 'assertResponseCodeIsRedirection', false],
            '399 not client error' => [399, 'assertResponseCodeIsClientError', false],
            '400 client error'     => [400, 'assertResponseCodeIsClientError', true],
            '499 client error'     => [499, 'assertResponseCodeIsClientError', true],
            '500 not client error' => [500, 'assertResponseCodeIsClientError', false],
            '499 not server error' => [499, 'assertResponseCodeIsServerError', false],
            '500 server error'     => [500, 'assertResponseCodeIsServerError', true],
            '599 server error'     => [599, 'assertResponseCodeIsServerError', true],
            '600 not server error' => [600, 'assertResponseCodeIsServerError', false],
        ];
    }

    public function testAssertHttpHeaderFailsWhenTheHeaderIsAbsent(): void
    {
        $this->respondWith('{}');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches("/header 'X-Absent' is present/");

        $this->assertHttpHeader('X-Absent');
    }

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

    public function testAssertResponseCodeIsNotFailsWhenTheCodeMatches(): void
    {
        $this->respondWith('{}', 200);

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseCodeIsNot(200);
    }

    public function testAssertResponseContainsFailsWhenTheTextIsAbsent(): void
    {
        $this->respondWith('{"a":1}');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseContains('"b"');
    }

    /**
     * ['data' => []] must assert that data IS empty, not merely that the key is
     * there - the vacuous reading is the one a caller never means.
     */
    public function testAssertResponseContainsJsonEmptyListMeansEmpty(): void
    {
        $this->respondWith('{"data":[{"id":1}]}');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseContainsJson(['data' => []]);
    }

    /**
     * The failure message carries the response body - without it a failing
     * fragment assertion says nothing about what actually came back.
     */
    public function testAssertResponseContainsJsonFailsAndNamesTheResponse(): void
    {
        $this->respondWith('{"data":[{"name":"Acme"}]}');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('/Response: \{"data":\[\{"name":"Acme"\}\]\}/');

        $this->assertResponseContainsJson(['data' => [['name' => 'Nope']]]);
    }

    public function testAssertResponseContainsJsonMatchesFragment(): void
    {
        $this->respondWith('{"jsonapi":{"version":"1.0"},"data":[{"id":1,"name":"Acme"}]}');

        $this->assertResponseContainsJson(['data' => [['name' => 'Acme']]]);
        $this->assertResponseNotContainsJson(['data' => [['name' => 'Other']]]);
    }

    public function testAssertResponseContainsJsonRejectsAnEmptyFragment(): void
    {
        $this->respondWith('{"a":1}');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches(
            '/An empty fragment asserts nothing; pass the fragment you mean to assert/'
        );

        $this->assertResponseContainsJson([]);
    }

    public function testAssertResponseEqualsFailsOnADifferentBody(): void
    {
        $this->respondWith('{"a":1}');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseEquals('{"a":2}');
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

    public function testAssertResponseIsJsonFailureNamesTheResponse(): void
    {
        $this->respondWith('not json at all');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('/Response: not json at all/');

        $this->assertResponseIsJson();
    }

    public function testAssertResponseMatchesJsonType(): void
    {
        $this->respondWith(
            '{"jsonapi":{"version":"1.0"},'
            . '"meta":{"timestamp":"2026-07-15T10:30:00+00:00",'
            . '"hash":"abc"}}'
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

    public function testAssertResponseNotContainsFailsWhenTheTextIsPresent(): void
    {
        $this->respondWith('{"a":1}');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseNotContains('"a"');
    }

    public function testAssertResponseNotContainsJsonFailsWhenTheFragmentMatches(): void
    {
        $this->respondWith('{"data":[{"name":"Acme"}]}');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseNotContainsJson(['data' => [['name' => 'Acme']]]);
    }

    public function testAssertResponseNotContainsJsonRejectsAnEmptyFragment(): void
    {
        $this->respondWith('{"a":1}');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches(
            '/An empty fragment asserts nothing; pass the fragment you mean to assert/'
        );

        $this->assertResponseNotContainsJson([]);
    }

    public function testAssertResponseNotMatchesJsonType(): void
    {
        $this->respondWith('{"meta":{"hash":1}}');

        $this->assertResponseNotMatchesJsonType(['meta' => ['hash' => 'string']]);
    }

    public function testAssertResponseNotMatchesJsonTypeFailsWhenTheTypesMatch(): void
    {
        $this->respondWith('{"meta":{"hash":"abc"}}');

        $this->expectException(AssertionFailedError::class);

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

    public function testHeaderAssertionsFailOnAWrongValue(): void
    {
        $this->respondWith('{}');

        $this->expectException(AssertionFailedError::class);

        $this->assertHttpHeader('Content-Type', 'text/html');
    }

    public function testJsonAssertionsTreatANonObjectResponseAsEmpty(): void
    {
        $this->respondWith('"just a string"');

        $this->assertResponseNotContainsJson(['a' => 1]);
    }

    public function testNoHttpHeaderFailsWhenTheHeaderIsPresent(): void
    {
        $this->respondWith('{}');

        $this->expectException(AssertionFailedError::class);

        $this->assertNoHttpHeader('Content-Type');
    }

    public function testNoHttpHeaderFailsWhenTheValueMatches(): void
    {
        $this->respondWith('{}');

        $this->expectException(AssertionFailedError::class);

        $this->assertNoHttpHeader('Content-Type', 'application/json');
    }

    /**
     * The range assertions are two comparisons against literal bounds, so each
     * bound must be pinned from both sides - a test that only uses a mid-range
     * code cannot tell 400..499 from 401..498.
     *
     * @dataProvider providerRangeBoundaries
     */
    public function testRangeAssertionBoundaries(
        int $code,
        string $method,
        bool $shouldPass
    ): void {
        $this->respondWith('{}', $code);

        if (!$shouldPass) {
            $this->expectException(AssertionFailedError::class);
        }

        $this->$method();

        // On the passing path $method() has already asserted the bounds; assert
        // the code round-tripped so the test carries an assertion of its own.
        $this->assertSame($code, $this->grabResponseCode());
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
