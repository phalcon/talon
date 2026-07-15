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

namespace Phalcon\Talon\Tests\Unit\Http;

use Phalcon\Talon\Http\HttpCode;
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;

use function constant;

final class HttpCodeTest extends AbstractUnitTestCase
{
    /**
     * @return array<int, array{0: string, 1: int}>
     */
    public static function providerConstants(): array
    {
        return [
            ['ACCEPTED', 202],
            ['BAD_GATEWAY', 502],
            ['BAD_REQUEST', 400],
            ['CONFLICT', 409],
            ['CREATED', 201],
            ['FORBIDDEN', 403],
            ['FOUND', 302],
            ['GATEWAY_TIMEOUT', 504],
            ['GONE', 410],
            ['INTERNAL_SERVER_ERROR', 500],
            ['METHOD_NOT_ALLOWED', 405],
            ['MOVED_PERMANENTLY', 301],
            ['NOT_ACCEPTABLE', 406],
            ['NOT_FOUND', 404],
            ['NOT_IMPLEMENTED', 501],
            ['NOT_MODIFIED', 304],
            ['NO_CONTENT', 204],
            ['OK', 200],
            ['PERMANENT_REDIRECT', 308],
            ['SEE_OTHER', 303],
            ['SERVICE_UNAVAILABLE', 503],
            ['TEMPORARY_REDIRECT', 307],
            ['TOO_MANY_REQUESTS', 429],
            ['UNAUTHORIZED', 401],
            ['UNPROCESSABLE_ENTITY', 422],
            ['UNSUPPORTED_MEDIA_TYPE', 415],
        ];
    }

    /**
     * @return array<int, array{0: int, 1: string}>
     */
    public static function providerDescriptions(): array
    {
        return [
            [200, '200 (OK)'],
            [201, '201 (Created)'],
            [202, '202 (Accepted)'],
            [204, '204 (No Content)'],
            [301, '301 (Moved Permanently)'],
            [302, '302 (Found)'],
            [303, '303 (See Other)'],
            [304, '304 (Not Modified)'],
            [307, '307 (Temporary Redirect)'],
            [308, '308 (Permanent Redirect)'],
            [400, '400 (Bad Request)'],
            [401, '401 (Unauthorized)'],
            [403, '403 (Forbidden)'],
            [404, '404 (Not Found)'],
            [405, '405 (Method Not Allowed)'],
            [406, '406 (Not Acceptable)'],
            [409, '409 (Conflict)'],
            [410, '410 (Gone)'],
            [415, '415 (Unsupported Media Type)'],
            [422, '422 (Unprocessable Entity)'],
            [429, '429 (Too Many Requests)'],
            [500, '500 (Internal Server Error)'],
            [501, '501 (Not Implemented)'],
            [502, '502 (Bad Gateway)'],
            [503, '503 (Service Unavailable)'],
            [504, '504 (Gateway Timeout)'],
        ];
    }

    /**
     * Every constant must resolve to the code it names, and every constant must
     * have a phrase - a constant without one would silently report 'Unknown'.
     *
     * @dataProvider providerConstants
     */
    public function testConstantResolvesToItsCodeAndHasAPhrase(string $name, int $code): void
    {
        $this->assertSame($code, constant(HttpCode::class . '::' . $name));
        $this->assertStringNotContainsString('Unknown', HttpCode::getDescription($code));
    }

    public function testGetDescriptionForUnknownCode(): void
    {
        $this->assertSame('599 (Unknown)', HttpCode::getDescription(599));
    }

    /**
     * @dataProvider providerDescriptions
     */
    public function testGetDescriptionReturnsCodeAndPhrase(int $code, string $expected): void
    {
        $this->assertSame($expected, HttpCode::getDescription($code));
    }
}
