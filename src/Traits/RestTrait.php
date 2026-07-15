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
use function basename;
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
    private ?string $restBaseUrlOverride = null;

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

    /**
     * Returns the first value of a response header. A header sent more than
     * once (Set-Cookie being the usual case) reports only its first value.
     */
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

    /**
     * Sets a request header for this and every later request, until it is
     * unset.
     */
    public function haveHttpHeader(string $name, string $value): void
    {
        $this->restRequestHeaders[$this->headerKey($name)] = $value;
    }

    /**
     * @param array<array-key, mixed>|string $params
     * @param array<string, mixed>           $files
     */
    public function send(string $method, string $url, array|string $params = [], array $files = []): void
    {
        $method = strtoupper($method);
        $uri    = $this->restUrl($url);

        if (is_string($params)) {
            // HttpBrowser wraps an unlabelled string body in a TextPart, which
            // goes out as text/plain. On a REST surface a raw body is JSON far
            // more often than not, so name it - per request, and only when the
            // caller has not named it already (the union keeps the left side).
            $server = $this->restRequestHeaders + [$this->headerKey('Content-Type') => 'application/json'];

            $this->restBrowser()->request($method, $uri, [], $this->normalizeFiles($files), $server, $params);

            return;
        }

        // Files force a body, so a bodyless verb carrying them falls through to
        // the multipart path rather than silently dropping them.
        if ([] === $files && in_array($method, ['DELETE', 'GET', 'HEAD', 'OPTIONS'], true)) {
            if ([] !== $params) {
                $uri .= (str_contains($uri, '?') ? '&' : '?') . http_build_query($params);
            }

            $this->restBrowser()->request($method, $uri, [], [], $this->restRequestHeaders);

            return;
        }

        if ([] === $files && $this->sendsJson()) {
            $this->restBrowser()->request(
                $method,
                $uri,
                [],
                [],
                $this->restRequestHeaders,
                (string) json_encode($params)
            );

            return;
        }

        $this->restBrowser()->request($method, $uri, $params, $this->normalizeFiles($files), $this->restRequestHeaders);
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
     * @param array<string, mixed>           $files
     */
    public function sendPatch(string $url, array|string $params = [], array $files = []): void
    {
        $this->send('PATCH', $url, $params, $files);
    }

    /**
     * @param array<array-key, mixed>|string $params
     * @param array<string, mixed>           $files
     */
    public function sendPost(string $url, array|string $params = [], array $files = []): void
    {
        $this->send('POST', $url, $params, $files);
    }

    /**
     * @param array<array-key, mixed>|string $params
     * @param array<string, mixed>           $files
     */
    public function sendPut(string $url, array|string $params = [], array $files = []): void
    {
        $this->send('PUT', $url, $params, $files);
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
    }

    /**
     * Points this test at a base URL, ahead of the one Settings resolves.
     * Without it the URL comes from TALON_REST_URL via Talon's shared Settings,
     * which is process-global and cannot vary per test.
     */
    public function useRestBaseUrl(string $url): void
    {
        $this->restBaseUrlOverride = $url;
    }

    protected function restBaseUrl(): string
    {
        if (null !== $this->restBaseUrlOverride) {
            return $this->restBaseUrlOverride;
        }

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

    /**
     * Header names map onto PHP's HTTP_* server convention, so '-' and '_' are
     * indistinguishable: 'Content-Type' and 'Content_Type' address the same
     * header and the later call wins.
     */
    private function headerKey(string $name): string
    {
        return 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    }

    /**
     * Accepts either a plain path or the $_FILES shape BrowserKit wants.
     * BrowserKit's own handling of a plain path is to stop reading the list and
     * send nothing at all, so a caller who passes the obvious thing gets a
     * silently file-less request.
     *
     * @param array<string, mixed> $files
     *
     * @return array<string, mixed>
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $field => $file) {
            $normalized[$field] = is_string($file)
                ? ['tmp_name' => $file, 'name' => basename($file)]
                : $file;
        }

        return $normalized;
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
}
