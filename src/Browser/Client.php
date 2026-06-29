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
use Phalcon\Http\Response as PhalconResponse;
use Phalcon\Http\Response\Headers;
use Phalcon\Talon\Exceptions\InvalidApplication;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;

use function assert;
use function get_debug_type;
use function is_object;
use function method_exists;
use function parse_str;
use function parse_url;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;

final class Client extends AbstractBrowser
{
    public function __construct(private Closure $appFactory)
    {
        parent::__construct();
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

            return $this->normalizeResponse($response);
        } finally {
            [$_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER] = $backup;
        }
    }

    /**
     * The only method that names Phalcon's response shape - the v7/8 seam.
     */
    private function normalizeResponse(PhalconResponse $response): BrowserKitResponse
    {
        $bag = $response->getHeaders();
        assert($bag instanceof Headers);

        $status  = $response->getStatusCode() ?: 200;
        $headers = $bag->toArray();
        $content = (string) $response->getContent();

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        }

        return new BrowserKitResponse($content, $status, $headers);
    }
}
