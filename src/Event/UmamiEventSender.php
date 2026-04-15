<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Event;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Exception\ApiException;

final class UmamiEventSender
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApiConfig $config,
    ) {
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>> $data
     */
    public function send(
        string $websiteId,
        string $url,
        string $hostname,
        ?string $eventName = null,
        string $screen = '1920x1080',
        string $language = 'en-GB',
        string $title = '',
        string $referrer = '',
        ?string $tag = null,
        ?string $sessionId = null,
        array $data = [],
        string $userAgent = 'TlbUmamiBundle/1.0',
    ): array {
        $payload = [
            'website' => $websiteId,
            'url' => $url,
            'hostname' => $hostname,
            'screen' => $screen,
            'language' => $language,
            'title' => $title,
            'referrer' => $referrer,
        ];

        if (null !== $eventName && '' !== $eventName) {
            $payload['name'] = $eventName;
        }

        if (null !== $tag && '' !== $tag) {
            $payload['tag'] = $tag;
        }

        if (null !== $sessionId && '' !== $sessionId) {
            $payload['sessionId'] = $sessionId;
        }

        if ([] !== $data) {
            $payload['data'] = $data;
        }

        try {
            $response = $this->httpClient->request('POST', $this->joinPath('/api/send'), [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => $userAgent,
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new ApiException('Could not send Umami event.', previous: $e);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ApiException(
                sprintf('Umami event endpoint returned HTTP %d.', $statusCode),
                $statusCode,
                $this->decodeJsonBody($rawBody),
            );
        }

        return $this->decodeJsonBody($rawBody) ?? [];
    }

    private function joinPath(string $path): string
    {
        return rtrim($this->config->getBaseUrl(), '/').'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonBody(string $body): ?array
    {
        if ('' === trim($body)) {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($body, true);
        if (!\is_array($decoded)) {
            return null;
        }

        return $decoded;
    }
}
