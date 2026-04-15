<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Event\UmamiEventSender;

final class UmamiEventSenderTest extends TestCase
{
    public function testSendsExpectedPayloadAndHeaders(): void
    {
        $capturedMethod = '';
        $capturedUrl = '';
        $capturedOptions = [];

        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedMethod, &$capturedUrl, &$capturedOptions): MockResponse {
            $capturedMethod = $method;
            $capturedUrl = $url;
            $capturedOptions = $options;

            return new MockResponse('{"ok":true}', ['http_code' => 200]);
        });

        $sender = new UmamiEventSender(
            $client,
            new ApiConfig(true, 'cloud', 'https://analytics.example.com', null, null, 'k'),
        );

        $result = $sender->send(
            websiteId: 'website-id',
            url: 'https://example.com/pricing',
            hostname: 'example.com',
            eventName: 'cta_click',
            tag: 'marketing',
            sessionId: 'session-1',
            data: ['plan' => 'pro'],
        );
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) ($capturedOptions['body'] ?? '{}'), true) ?? [];

        self::assertSame(['ok' => true], $result);
        self::assertSame('POST', $capturedMethod);
        self::assertSame('https://analytics.example.com/api/send', $capturedUrl);
        self::assertSame('Accept: application/json', $capturedOptions['normalized_headers']['accept'][0] ?? null);
        self::assertSame('User-Agent: TlbUmamiBundle/1.0', $capturedOptions['normalized_headers']['user-agent'][0] ?? null);
        self::assertSame('website-id', $payload['website'] ?? null);
        self::assertSame('cta_click', $payload['name'] ?? null);
        self::assertSame('marketing', $payload['tag'] ?? null);
        self::assertSame(['plan' => 'pro'], $payload['data'] ?? null);
    }
}
