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

use Phalcon\Talon\Exceptions\ElementNotFound;
use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\PHPUnit\AbstractBrowserTestCase;

final class BrowserTraitTest extends AbstractBrowserTestCase
{
    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/../Fakes/Browser/app.php';
    }

    public function testVisitAndAssertPageText(): void
    {
        $this->visitPage('/browser/form');

        $this->assertPageContainsText('Log In');
        $this->assertPageMissingText('Welcome back');
    }

    public function testAssertBeforeVisitThrows(): void
    {
        $this->expectException(ResponseNotDispatched::class);

        $this->assertPageContainsText('anything');
    }

    public function testLoginFlowFollowsRedirectAndKeepsSession(): void
    {
        $this->visitPage('/browser/form');
        $this->fillField('name', 'sarah');
        $this->selectOption('active', 'Yes');
        $this->pressButton('Log In');

        $this->assertPageContainsText('Welcome sarah');
        $this->assertPageContainsText('active=Yes');
    }

    public function testSessionPersistsAcrossASecondRequest(): void
    {
        $this->visitPage('/browser/form');
        $this->fillField('name', 'sarah');
        $this->pressButton('Log In');

        $this->visitPage('/browser/secured');
        $this->assertPageContainsText('Welcome sarah');
    }

    public function testSecuredIsGuestInAFreshTest(): void
    {
        $this->visitPage('/browser/secured');

        $this->assertPageContainsText('Guest');
    }

    public function testPressButtonBySelectorSubmitsTheForm(): void
    {
        $this->visitPage('/browser/form');
        $this->fillField('name', 'john');
        $this->pressButton('//form/*[@type="submit"]');

        $this->assertPageContainsText('Welcome john');
    }

    public function testClickLinkByText(): void
    {
        $this->visitPage('/browser/menu');
        $this->clickLink('Go');

        $this->assertPageContainsText('landed ok');
    }

    public function testClickLinkWithinContext(): void
    {
        $this->visitPage('/browser/menu');
        $this->clickLink('Open', '//tr[td[contains(., "Row A")]]');

        $this->assertPageContainsText('landed ok');
    }

    public function testPressButtonByLabelWithoutFilling(): void
    {
        $this->visitPage('/browser/search');
        $this->pressButton('Search');

        $this->assertPageContainsText('landed ok');
    }

    public function testCookieJarReadAndWrite(): void
    {
        $this->visitPage('/browser/cookie');
        $this->assertSame('yummy', $this->getCookie('baked'));

        $this->setCookie('talon', 'crunchy');
        $this->visitPage('/browser/cookie');
        $this->assertPageContainsText('cookie sent=crunchy');
    }

    public function testMissingLinkThrows(): void
    {
        $this->visitPage('/browser/menu');

        $this->expectException(ElementNotFound::class);

        $this->clickLink('Nonexistent');
    }

    public function testGetCookieReturnsNullWhenAbsent(): void
    {
        $this->visitPage('/browser/menu');

        $this->assertNull($this->getCookie('nope'));
    }

    public function testPressButtonWithNoFormThrows(): void
    {
        $this->visitPage('/browser/landed');

        $this->expectException(ElementNotFound::class);

        $this->pressButton('Save');
    }

    public function testPressButtonByXPathWithoutFilling(): void
    {
        $this->visitPage('/browser/search');
        $this->pressButton('//button');

        $this->assertPageContainsText('landed ok');
    }

    public function testFillFieldWithNoFormThrows(): void
    {
        $this->visitPage('/browser/landed');

        $this->expectException(ElementNotFound::class);

        $this->fillField('name', 'value');
    }
}
