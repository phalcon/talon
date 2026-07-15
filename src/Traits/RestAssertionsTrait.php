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

use Phalcon\Talon\Http\JsonSubset;
use Phalcon\Talon\Http\JsonType;

use function is_array;
use function json_decode;
use function json_last_error;
use function sprintf;

use const JSON_ERROR_NONE;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait RestAssertionsTrait
{
    public function assertHttpHeader(string $name, ?string $value = null): void
    {
        $actual = $this->grabHttpHeader($name);

        $this->assertNotNull($actual, sprintf("Failed asserting that header '%s' is present", $name));

        if (null !== $value) {
            $this->assertSame($value, $actual);
        }
    }

    public function assertNoHttpHeader(string $name, ?string $value = null): void
    {
        $actual = $this->grabHttpHeader($name);

        if (null === $value) {
            $this->assertNull($actual, sprintf("Failed asserting that header '%s' is absent", $name));

            return;
        }

        $this->assertNotSame($value, $actual);
    }

    public function assertResponseCodeIs(int $code): void
    {
        $this->assertSame($code, $this->grabResponseCode());
    }

    public function assertResponseCodeIsClientError(): void
    {
        $this->assertResponseCodeInRange(400, 499);
    }

    public function assertResponseCodeIsNot(int $code): void
    {
        $this->assertNotSame($code, $this->grabResponseCode());
    }

    public function assertResponseCodeIsRedirection(): void
    {
        $this->assertResponseCodeInRange(300, 399);
    }

    public function assertResponseCodeIsServerError(): void
    {
        $this->assertResponseCodeInRange(500, 599);
    }

    public function assertResponseCodeIsSuccessful(): void
    {
        $this->assertResponseCodeInRange(200, 299);
    }

    public function assertResponseContains(string $text): void
    {
        $this->assertStringContainsString($text, $this->grabResponse());
    }

    /**
     * @param array<array-key, mixed> $json
     */
    public function assertResponseContainsJson(array $json): void
    {
        $this->assertNotSame([], $json, $this->emptyFragmentMessage());

        $this->assertTrue(
            JsonSubset::contains($this->decodedResponse(), $json),
            'Failed asserting that the response contains the given JSON fragment. Response: '
            . $this->grabResponse()
        );
    }

    public function assertResponseEquals(string $expected): void
    {
        $this->assertSame($expected, $this->grabResponse());
    }

    public function assertResponseIsJson(): void
    {
        $content = $this->grabResponse();
        json_decode($content, true);

        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'Failed asserting that the response is valid JSON. Response: ' . $content
        );
    }

    /**
     * @param array<array-key, mixed> $types
     */
    public function assertResponseMatchesJsonType(array $types): void
    {
        $error = JsonType::match($this->decodedResponse(), $types);

        $this->assertNull(
            $error,
            sprintf('Failed asserting that the response matches the given JSON types. %s', (string) $error)
        );
    }

    public function assertResponseNotContains(string $text): void
    {
        $this->assertStringNotContainsString($text, $this->grabResponse());
    }

    /**
     * @param array<array-key, mixed> $json
     */
    public function assertResponseNotContainsJson(array $json): void
    {
        $this->assertNotSame([], $json, $this->emptyFragmentMessage());

        $this->assertFalse(
            JsonSubset::contains($this->decodedResponse(), $json),
            'Failed asserting that the response does not contain the given JSON fragment.'
        );
    }

    /**
     * @param array<array-key, mixed> $types
     */
    public function assertResponseNotMatchesJsonType(array $types): void
    {
        $this->assertNotNull(
            JsonType::match($this->decodedResponse(), $types),
            'Failed asserting that the response does not match the given JSON types.'
        );
    }
    abstract public function grabHttpHeader(string $name): ?string;

    abstract public function grabResponse(): string;

    abstract public function grabResponseCode(): int;

    private function assertResponseCodeInRange(int $low, int $high): void
    {
        $code = $this->grabResponseCode();

        $this->assertGreaterThanOrEqual($low, $code);
        $this->assertLessThanOrEqual($high, $code);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodedResponse(): array
    {
        $decoded = json_decode($this->grabResponse(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Traits cannot declare constants until PHP 8.2, so this stands in for one.
     */
    private function emptyFragmentMessage(): string
    {
        return 'An empty fragment asserts nothing about the response; '
            . 'pass the fragment you mean to assert.';
    }
}
