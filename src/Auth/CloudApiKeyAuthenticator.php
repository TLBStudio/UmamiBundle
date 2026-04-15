<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Auth;

use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Exception\AuthenticationException;

final class CloudApiKeyAuthenticator implements AuthenticatorInterface
{
    public function __construct(private readonly ApiConfig $config)
    {
    }

    public function getHeaders(): array
    {
        $apiKey = $this->config->getApiKey();
        if (null === $apiKey || '' === $apiKey) {
            throw new AuthenticationException('Cloud API key is not configured.');
        }

        return ['x-umami-api-key' => $apiKey];
    }

    public function invalidate(): void
    {
    }
}
