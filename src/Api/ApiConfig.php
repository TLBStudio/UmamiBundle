<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Api;

final class ApiConfig
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $mode,
        private readonly string $baseUrl,
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly ?string $apiKey,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isCloudMode(): bool
    {
        return 'cloud' === $this->mode;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }
}
