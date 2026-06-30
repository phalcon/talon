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

namespace Phalcon\Talon\Browser;

use Closure;
use Phalcon\Di\DiInterface;
use Phalcon\Http\Cookie\CookieInterface;
use Phalcon\Http\Response as PhalconResponse;
use Phalcon\Http\Response\Cookies;
use Phalcon\Http\Response\Headers;
use Phalcon\Talon\Exceptions\InvalidApplication;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;

use function assert;
use function get_debug_type;
use function gmdate;
use function is_object;
use function is_scalar;
use function method_exists;
use function parse_str;
use function parse_url;
use function rawurlencode;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;

final class Client extends AbstractBrowser
{
    /**
     * Upper bound on the in-process redirect chain. Each redirect re-dispatches
     * the whole app, and BrowserKit follows them recursively; left unbounded
     * (its default is -1) a cycle recurses until the runtime's stack overflows
     * and crashes. Capping it lets BrowserKit raise a clean exception instead.
     */
    private const MAX_REDIRECTS = 20;

    public function __construct(private Closure $appFactory)
    {
        parent::__construct();

        $this->setMaxRedirects(self::MAX_REDIRECTS);
    }

    protected function doRequest(object $request): object
    {
        assert($request instanceof BrowserKitRequest);

        // A fresh app per request keeps each dispatch clean (the dispatcher is
        // single-use); session continuity is provided by the process-global
        // $_SESSION, not by the app instance.
        $factory = $this->appFactory;
        $app     = $factory();
        if (!is_object($app) || !method_exists($app, 'handle')) {
            throw new InvalidApplication(get_debug_type($app));
        }

        $uri   = (string) $request->getUri();
        $path  = (string) parse_url($uri, PHP_URL_PATH);
        $query = (string) parse_url($uri, PHP_URL_QUERY);

        $get = [];
        parse_str($query, $get);

        $backup = [$_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER];

        $_GET                      = $get;
        $_POST                     = 'GET' === $request->getMethod() ? [] : $request->getParameters();
        $_COOKIE                   = $request->getCookies();
        $_REQUEST                  = $_GET + $_POST;
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = '' === $query ? $path : $path . '?' . $query;

        try {
            $response = $app->handle($path);
            assert($response instanceof PhalconResponse);

            return $this->normalizeResponse($response, $this->extractSetCookies($app));
        } finally {
            [$_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER] = $backup;
        }
    }

    /**
     * Phalcon only writes Set-Cookie headers when the response is sent, which the
     * in-process dispatch never does. Pull the cookies off the application's
     * cookies service so the BrowserKit jar can carry (and expire) them between
     * requests. Values are read raw, so the application under test must run with
     * cookie encryption disabled.
     *
     * @return array<int, string>
     */
    private function extractSetCookies(object $app): array
    {
        if (!method_exists($app, 'getDI')) {
            return [];
        }

        $container = $app->getDI();
        if (!$container instanceof DiInterface || !$container->has('cookies')) {
            return [];
        }

        $service = $container->getShared('cookies');
        if (!$service instanceof Cookies) {
            return [];
        }

        $headers = [];
        foreach ($service->getCookies() as $cookie) {
            if (!$cookie instanceof CookieInterface) {
                continue;
            }

            $value = $cookie->getValue();
            if (!is_scalar($value)) {
                continue;
            }

            $header = $cookie->getName() . '=' . rawurlencode((string) $value)
                . '; Path=' . ($cookie->getPath() ?: '/');

            $expires = $cookie->getExpiration();
            if ($expires > 0) {
                $header .= '; Expires=' . gmdate('D, d-M-Y H:i:s', $expires) . ' GMT';
            }

            $headers[] = $header;
        }

        return $headers;
    }

    /**
     * The only method that names Phalcon's response shape - the v7/8 seam.
     *
     * @param array<int, string> $setCookies
     */
    private function normalizeResponse(PhalconResponse $response, array $setCookies = []): BrowserKitResponse
    {
        $bag = $response->getHeaders();
        assert($bag instanceof Headers);

        $status  = $response->getStatusCode() ?: 200;
        $headers = $bag->toArray();
        $content = (string) $response->getContent();

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        }

        if ([] !== $setCookies) {
            $headers['Set-Cookie'] = $setCookies;
        }

        return new BrowserKitResponse($content, $status, $headers);
    }
}
