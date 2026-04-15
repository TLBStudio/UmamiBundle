<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tests\Client;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Auth\AuthenticatorInterface;
use Tlb\UmamiBundle\Client\UmamiApiClient;
use Tlb\UmamiBundle\Exception\ApiException;

final class UmamiApiClientTest extends TestCase
{
    public function testUsesAuthenticatorHeaders(): void
    {
        $capturedOptions = null;
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
            $capturedOptions = $options;

            return new MockResponse('{"ok":true}', ['http_code' => 200]);
        });

        $apiClient = new UmamiApiClient(
            $client,
            new ApiConfig(true, 'cloud', 'https://api-gateway.umami.is/', null, null, 'k'),
            new TestAuthenticator(['x-umami-api-key' => 'abc123']),
        );

        $response = $apiClient->request('GET', '/api/websites/test/stats');

        self::assertSame(['ok' => true], $response);
        self::assertSame('Accept: application/json', $capturedOptions['normalized_headers']['accept'][0] ?? null);
        self::assertSame('x-umami-api-key: abc123', strtolower($capturedOptions['normalized_headers']['x-umami-api-key'][0] ?? ''));
    }

    public function testRetriesOnceOnUnauthorizedResponse(): void
    {
        $responses = [
            new MockResponse('{"message":"unauthorized"}', ['http_code' => 401]),
            new MockResponse('{"value":42}', ['http_code' => 200]),
        ];
        $client = new MockHttpClient($responses);
        $authenticator = new TestAuthenticator(['Authorization' => 'Bearer token']);

        $apiClient = new UmamiApiClient(
            $client,
            new ApiConfig(true, 'self_hosted', 'https://umami.example.com', 'admin', 'pass', null),
            $authenticator,
        );

        $result = $apiClient->request('GET', '/api/websites/site/stats');

        self::assertSame(['value' => 42], $result);
        self::assertSame(1, $authenticator->invalidations);
    }

    public function testThrowsApiExceptionOnFailedRequest(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"message":"bad request"}', ['http_code' => 400]),
        ]);
        $apiClient = new UmamiApiClient(
            $client,
            new ApiConfig(true, 'cloud', 'https://api-gateway.umami.is', null, null, 'k'),
            new TestAuthenticator(['x-umami-api-key' => 'k']),
        );

        $this->expectException(ApiException::class);
        $apiClient->request('GET', '/api/websites/site/stats');
    }
}

final class TestAuthenticator implements AuthenticatorInterface
{
    public int $invalidations = 0;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(private readonly array $headers)
    {
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function invalidate(): void
    {
        ++$this->invalidations;
    }
}
