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

namespace Phalcon\Talon\Tests\Fakes\Rest;

use Phalcon\Talon\PHPUnit\AbstractRestTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Exposes the REST traits to a scope outside the AbstractRestTestCase
 * hierarchy, so tests can verify the public API stays publicly callable and
 * the protected restBaseUrl()/restBrowser()/restHttpClient() seams stay
 * subclass-accessible.
 */
final class PublicApiConsumer extends AbstractRestTestCase
{
    public const BODY = '{"jsonapi":{"version":"1.0"},"data":[{"id":1,"name":"Acme"}]}';

    private int $status = 200;

    public function rawBaseUrl(): string
    {
        return $this->restBaseUrl();
    }

    public function rawBrowser(): AbstractBrowser
    {
        return $this->restBrowser();
    }

    public function rawHttpClient(): ?HttpClientInterface
    {
        return $this->restHttpClient();
    }

    public function respondWith(int $status): void
    {
        $this->status = $status;
    }

    protected function restBaseUrl(): string
    {
        return 'http://api.test:8080';
    }

    protected function restHttpClient(): HttpClientInterface
    {
        return new MockHttpClient(fn (): MockResponse => new MockResponse(
            self::BODY,
            [
                'http_code'        => $this->status,
                'response_headers' => ['Content-Type' => 'application/json'],
            ]
        ));
    }
}
