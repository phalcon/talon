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

namespace Phalcon\Talon\Http;

use function sprintf;

/**
 * HTTP status codes and their standard reason phrases.
 *
 * getDescription() is deliberately an independent implementation of the
 * '<code> (<phrase>)' format rather than a lookup into the application under
 * test - an application asserting its own emitted string against its own table
 * would assert nothing.
 */
final class HttpCode
{
    public const ACCEPTED = 202;
    public const BAD_GATEWAY = 502;
    public const BAD_REQUEST = 400;
    public const CONFLICT = 409;
    public const CREATED = 201;
    public const FORBIDDEN = 403;
    public const FOUND = 302;
    public const GATEWAY_TIMEOUT = 504;
    public const GONE = 410;
    public const INTERNAL_SERVER_ERROR = 500;
    public const METHOD_NOT_ALLOWED = 405;
    public const MOVED_PERMANENTLY = 301;
    public const NO_CONTENT = 204;
    public const NOT_ACCEPTABLE = 406;
    public const NOT_FOUND = 404;
    public const NOT_IMPLEMENTED = 501;
    public const NOT_MODIFIED = 304;
    public const OK = 200;
    public const PERMANENT_REDIRECT = 308;
    public const SEE_OTHER = 303;
    public const SERVICE_UNAVAILABLE = 503;
    public const TEMPORARY_REDIRECT = 307;
    public const TOO_MANY_REQUESTS = 429;
    public const UNAUTHORIZED = 401;
    public const UNPROCESSABLE_ENTITY = 422;
    public const UNSUPPORTED_MEDIA_TYPE = 415;

    /** @var array<int, string> */
    private const PHRASES = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        409 => 'Conflict',
        410 => 'Gone',
        415 => 'Unsupported Media Type',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    public static function getDescription(int $code): string
    {
        return sprintf('%d (%s)', $code, self::PHRASES[$code] ?? 'Unknown');
    }
}
