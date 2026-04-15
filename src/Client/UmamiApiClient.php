<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Client;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tlb\UmamiBundle\Auth\AuthenticatorInterface;
use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Exception\ApiException;

final class UmamiApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApiConfig $config,
        private readonly AuthenticatorInterface $authenticator,
    ) {
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>> $query
     */
    public function getStats(string $websiteId, int $startAt, int $endAt, array $query = []): array
    {
        return $this->request('GET', sprintf('/api/websites/%s/stats', rawurlencode($websiteId)), [
            ...$query,
            'startAt' => $startAt,
            'endAt' => $endAt,
        ]);
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>> $query
     */
    public function getPageviews(string $websiteId, int $startAt, int $endAt, array $query = []): array
    {
        return $this->request('GET', sprintf('/api/websites/%s/pageviews', rawurlencode($websiteId)), [
            ...$query,
            'startAt' => $startAt,
            'endAt' => $endAt,
        ]);
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>> $query
     */
    public function getEventStats(string $websiteId, int $startAt, int $endAt, array $query = []): array
    {
        return $this->request('GET', sprintf('/api/websites/%s/events/stats', rawurlencode($websiteId)), [
            ...$query,
            'startAt' => $startAt,
            'endAt' => $endAt,
        ]);
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>> $query
     * @param array<string, mixed>|null                               $json
     */
    public function request(string $method, string $path, array $query = [], ?array $json = null): array
    {
        return $this->doRequest($method, $path, $query, $json, true);
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>> $query
     * @param array<string, mixed>|null                               $json
     */
    private function doRequest(string $method, string $path, array $query, ?array $json, bool $allowAuthRetry): array
    {
        try {
            $response = $this->httpClient->request($method, $this->joinPath($path), [
                'headers' => [
                    'Accept' => 'application/json',
                    ...$this->authenticator->getHeaders(),
                ],
                'query' => $query,
                'json' => $json,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new ApiException('Could not reach Umami API.', previous: $e);
        }

        if (($statusCode === 401 || $statusCode === 403) && $allowAuthRetry) {
            $this->authenticator->invalidate();

            return $this->doRequest($method, $path, $query, $json, false);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ApiException(
                sprintf('Umami API returned HTTP %d for %s %s.', $statusCode, $method, $path),
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
