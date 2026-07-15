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
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reaches restBaseUrl()/restHttpClient() from a subclass without overriding
 * them - PublicApiConsumer overrides both, which masks the trait's own
 * definitions and hides whether they are still reachable from a child class.
 */
final class DefaultSeamConsumer extends AbstractRestTestCase
{
    public function rawBaseUrl(): string
    {
        return $this->restBaseUrl();
    }

    public function rawHttpClient(): ?HttpClientInterface
    {
        return $this->restHttpClient();
    }
}
