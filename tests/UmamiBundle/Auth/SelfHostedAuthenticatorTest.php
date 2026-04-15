<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Auth\SelfHostedAuthenticator;

final class SelfHostedAuthenticatorTest extends TestCase
{
    public function testCachesLoginTokenInConfiguredCachePool(): void
    {
        $requestCount = 0;
        $client = new MockHttpClient(function () use (&$requestCount): MockResponse {
            ++$requestCount;

            return new MockResponse('{"token":"cached-token"}', ['http_code' => 200]);
        });

        $cache = new ArrayAdapter();
        $authenticator = new SelfHostedAuthenticator(
            $client,
            new ApiConfig(true, 'self_hosted', 'https://umami.example.com', 'admin', 'pass', null),
            $cache,
        );

        $first = $authenticator->getHeaders();
        $second = $authenticator->getHeaders();

        self::assertSame('Bearer cached-token', $first['Authorization']);
        self::assertSame($first, $second);
        self::assertSame(1, $requestCount);
    }

    public function testInvalidateForcesNewLogin(): void
    {
        $tokens = ['first-token', 'second-token'];
        $client = new MockHttpClient(function () use (&$tokens): MockResponse {
            $token = array_shift($tokens);

            return new MockResponse(sprintf('{"token":"%s"}', $token), ['http_code' => 200]);
        });

        $authenticator = new SelfHostedAuthenticator(
            $client,
            new ApiConfig(true, 'self_hosted', 'https://umami.example.com', 'admin', 'pass', null),
            new ArrayAdapter(),
        );

        $first = $authenticator->getHeaders();
        $authenticator->invalidate();
        $second = $authenticator->getHeaders();

        self::assertSame('Bearer first-token', $first['Authorization']);
        self::assertSame('Bearer second-token', $second['Authorization']);
    }
}
