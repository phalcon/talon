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

use Closure;
use Phalcon\Talon\Browser\Client;
use Phalcon\Talon\Exceptions\ElementNotFound;
use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\Exception\BadMethodCallException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use function str_starts_with;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait BrowserTrait
{
    private ?Client $client = null;

    private ?Form $form = null;

    public function clickLink(string $text, ?string $context = null): void
    {
        $crawler = null === $context
            ? $this->crawler()
            : $this->crawler()->filterXPath($context);

        $links = $crawler->selectLink($text);
        if (0 === $links->count()) {
            throw new ElementNotFound('link "' . $text . '"');
        }

        $this->browser()->click($links->link());
        $this->form = null;
    }

    public function fillField(string $name, string $value): void
    {
        $this->currentForm()->offsetSet($name, $value);
    }

    public function getCookie(string $name): ?string
    {
        $cookie = $this->browser()->getCookieJar()->get($name);

        return null === $cookie ? null : $cookie->getValue();
    }

    public function pressButton(string $labelOrSelector): void
    {
        $form = $this->form ?? $this->resolveForm($labelOrSelector);

        $this->browser()->submit($form);
        $this->form = null;
    }

    public function selectOption(string $name, string $value): void
    {
        $this->currentForm()->offsetSet($name, $value);
    }

    public function setCookie(string $name, string $value): void
    {
        // Scope the cookie to the host visitPage() uses. An empty domain (the
        // BrowserKit default) is sent on every request yet can never be expired
        // by a response Set-Cookie - those are bucketed under the request host -
        // so the app could never clear a test-set cookie.
        $this->browser()->getCookieJar()->set(new Cookie($name, $value, null, '/', 'localhost'));
    }

    public function visitPage(string $url): void
    {
        $this->browser()->request('GET', 'http://localhost' . $url);
        $this->form = null;
    }

    abstract protected function appFactory(): callable;

    protected function browser(): Client
    {
        if (null === $this->client) {
            $this->client = new Client(Closure::fromCallable($this->appFactory()));
        }

        return $this->client;
    }

    protected function crawler(): Crawler
    {
        try {
            return $this->browser()->getCrawler();
        } catch (BadMethodCallException) {
            throw new ResponseNotDispatched();
        }
    }

    private function currentForm(): Form
    {
        if (null === $this->form) {
            $forms = $this->crawler()->filterXPath('//form');
            if (0 === $forms->count()) {
                throw new ElementNotFound('form');
            }

            $this->form = $forms->form();
        }

        return $this->form;
    }

    private function resolveForm(string $labelOrSelector): Form
    {
        $crawler = $this->crawler();

        $buttons = $crawler->selectButton($labelOrSelector);
        if ($buttons->count() > 0) {
            return $buttons->form();
        }

        if (str_starts_with($labelOrSelector, '/') || str_starts_with($labelOrSelector, '(')) {
            $nodes = $crawler->filterXPath($labelOrSelector);
            if ($nodes->count() > 0) {
                return $nodes->form();
            }
        }

        throw new ElementNotFound('button "' . $labelOrSelector . '"');
    }
}
