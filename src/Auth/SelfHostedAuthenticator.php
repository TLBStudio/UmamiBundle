<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Auth;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Exception\AuthenticationException;

final class SelfHostedAuthenticator implements AuthenticatorInterface
{
    private const TOKEN_TTL_SECONDS = 3300;

    private ?string $inMemoryToken = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApiConfig $config,
        private readonly ?CacheItemPoolInterface $cachePool = null,
    ) {
    }

    public function getHeaders(): array
    {
        return ['Authorization' => sprintf('Bearer %s', $this->getToken())];
    }

    public function invalidate(): void
    {
        $this->inMemoryToken = null;

        if (null === $this->cachePool) {
            return;
        }

        $this->cachePool->deleteItem($this->cacheKey());
    }

    private function getToken(): string
    {
        if (null !== $this->inMemoryToken) {
            return $this->inMemoryToken;
        }

        if (null !== $this->cachePool) {
            $item = $this->cachePool->getItem($this->cacheKey());
            if ($item->isHit()) {
                $token = $item->get();
                if (\is_string($token) && '' !== $token) {
                    $this->inMemoryToken = $token;

                    return $token;
                }
            }
        }

        return $this->login();
    }

    private function login(): string
    {
        $username = $this->config->getUsername();
        $password = $this->config->getPassword();

        if (null === $username || null === $password) {
            throw new AuthenticationException('Self-hosted Umami credentials are not configured.');
        }

        try {
            $response = $this->httpClient->request('POST', $this->joinPath('/api/auth/login'), [
                'headers' => ['Accept' => 'application/json'],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);
            $statusCode = $response->getStatusCode();
            $rawBody = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new AuthenticationException('Could not authenticate with Umami.', previous: $e);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AuthenticationException(sprintf('Umami login failed with status %d.', $statusCode));
        }

        /** @var mixed $decoded */
        $decoded = json_decode($rawBody, true);
        if (!\is_array($decoded)) {
            throw new AuthenticationException('Umami login did not return valid JSON.');
        }

        $token = $decoded['token'] ?? $decoded['access_token'] ?? null;
        if (!\is_string($token) || '' === $token) {
            throw new AuthenticationException('Umami login response does not include a token.');
        }

        $this->inMemoryToken = $token;
        if (null !== $this->cachePool) {
            $item = $this->cachePool->getItem($this->cacheKey());
            $item->set($token);
            $item->expiresAfter(self::TOKEN_TTL_SECONDS);
            $this->cachePool->save($item);
        }

        return $token;
    }

    private function cacheKey(): string
    {
        return 'tlb_umami_auth_'.sha1($this->config->getBaseUrl().'|'.$this->config->getUsername());
    }

    private function joinPath(string $path): string
    {
        return rtrim($this->config->getBaseUrl(), '/').'/'.ltrim($path, '/');
    }
}
