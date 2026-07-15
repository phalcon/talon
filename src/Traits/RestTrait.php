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

namespace Phalcon\Talon\Traits;

use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\Talon;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Exception\BadMethodCallException;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\BrowserKit\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function base64_encode;
use function http_build_query;
use function in_array;
use function is_string;
use function json_encode;
use function ltrim;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function strtoupper;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait RestTrait
{
    private ?AbstractBrowser $restClient = null;

    /** @var array<string, string> */
    private array $restRequestHeaders = [];

    public function amBearerAuthenticated(string $token): void
    {
        $this->haveHttpHeader('Authorization', 'Bearer ' . $token);
    }

    public function amHttpAuthenticated(string $username, string $password): void
    {
        $this->haveHttpHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $password));
    }

    public function grabHttpHeader(string $name): ?string
    {
        // getHeader() is declared string|array|null, but the array arm is only
        // reachable with $first = false.
        /** @var string|null $value */
        $value = $this->restResponse()->getHeader($name);

        return $value;
    }

    public function grabResponse(): string
    {
        return $this->restResponse()->getContent();
    }

    public function grabResponseCode(): int
    {
        return $this->restResponse()->getStatusCode();
    }

    public function haveHttpHeader(string $name, string $value): void
    {
        $this->restRequestHeaders[$this->headerKey($name)] = $value;
        $this->syncRequestHeaders();
    }

    /**
     * @param array<array-key, mixed>|string $params
     */
    public function send(string $method, string $url, array|string $params = []): void
    {
        $method = strtoupper($method);
        $uri    = $this->restUrl($url);

        if (is_string($params)) {
            $this->restBrowser()->request($method, $uri, [], [], [], $params);

            return;
        }

        if (in_array($method, ['DELETE', 'GET', 'HEAD', 'OPTIONS'], true)) {
            if ([] !== $params) {
                $uri .= (str_contains($uri, '?') ? '&' : '?') . http_build_query($params);
            }

            $this->restBrowser()->request($method, $uri);

            return;
        }

        if ($this->sendsJson()) {
            $this->restBrowser()->request($method, $uri, [], [], [], (string) json_encode($params));

            return;
        }

        $this->restBrowser()->request($method, $uri, $params);
    }

    /**
     * @param array<array-key, mixed> $params
     */
    public function sendDelete(string $url, array $params = []): void
    {
        $this->send('DELETE', $url, $params);
    }

    /**
     * @param array<array-key, mixed> $params
     */
    public function sendGet(string $url, array $params = []): void
    {
        $this->send('GET', $url, $params);
    }

    /**
     * @param array<array-key, mixed> $params
     */
    public function sendHead(string $url, array $params = []): void
    {
        $this->send('HEAD', $url, $params);
    }

    /**
     * @param array<array-key, mixed> $params
     */
    public function sendOptions(string $url, array $params = []): void
    {
        $this->send('OPTIONS', $url, $params);
    }

    /**
     * @param array<array-key, mixed>|string $params
     */
    public function sendPatch(string $url, array|string $params = []): void
    {
        $this->send('PATCH', $url, $params);
    }

    /**
     * @param array<array-key, mixed>|string $params
     */
    public function sendPost(string $url, array|string $params = []): void
    {
        $this->send('POST', $url, $params);
    }

    /**
     * @param array<array-key, mixed>|string $params
     */
    public function sendPut(string $url, array|string $params = []): void
    {
        $this->send('PUT', $url, $params);
    }

    public function startFollowingRedirects(): void
    {
        $this->restBrowser()->followRedirects(true);
    }

    public function stopFollowingRedirects(): void
    {
        $this->restBrowser()->followRedirects(false);
    }

    public function unsetHttpHeader(string $name): void
    {
        unset($this->restRequestHeaders[$this->headerKey($name)]);
        $this->syncRequestHeaders();
    }

    protected function restBaseUrl(): string
    {
        $url = Talon::settings()->get('rest_url');

        return is_string($url) && '' !== $url ? $url : 'http://127.0.0.1:8080';
    }

    protected function restBrowser(): AbstractBrowser
    {
        if (null !== $this->restClient) {
            return $this->restClient;
        }

        $client = new HttpBrowser($this->restHttpClient());
        $client->followRedirects(false);

        $this->restClient = $client;
        $this->syncRequestHeaders();

        return $client;
    }

    /**
     * The transport seam. Returning null lets HttpBrowser build a real client;
     * a test overrides this with a MockHttpClient so the suite exercises the
     * same request-building path without a live server.
     */
    protected function restHttpClient(): ?HttpClientInterface
    {
        return null;
    }

    private function headerKey(string $name): string
    {
        return 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    }

    private function restResponse(): Response
    {
        try {
            return $this->restBrowser()->getInternalResponse();
        } catch (BadMethodCallException) {
            throw new ResponseNotDispatched();
        }
    }

    private function restUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($this->restBaseUrl(), '/') . '/' . ltrim($url, '/');
    }

    private function sendsJson(): bool
    {
        return str_contains(
            strtolower($this->restRequestHeaders['HTTP_CONTENT_TYPE'] ?? ''),
            'json'
        );
    }

    private function syncRequestHeaders(): void
    {
        if (null === $this->restClient) {
            return;
        }

        // setServerParameters() replaces the whole set (defaults plus what we
        // pass), which is the only way to drop a header - BrowserKit has no
        // removeServerParameter().
        $this->restClient->setServerParameters($this->restRequestHeaders);
    }
}
