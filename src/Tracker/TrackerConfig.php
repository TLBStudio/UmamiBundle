<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tracker;

final class TrackerConfig
{
    /**
     * @param list<string> $domains
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly string $scriptUrl,
        private readonly string $websiteId,
        private readonly ?string $hostUrl,
        private readonly bool $autoTrack,
        private readonly bool $doNotTrack,
        private readonly array $domains,
        private readonly ?string $tag,
        private readonly bool $cache,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getScriptUrl(): string
    {
        return $this->scriptUrl;
    }

    public function getWebsiteId(): string
    {
        return $this->websiteId;
    }

    public function getHostUrl(): ?string
    {
        return $this->hostUrl;
    }

    public function isAutoTrack(): bool
    {
        return $this->autoTrack;
    }

    public function isDoNotTrack(): bool
    {
        return $this->doNotTrack;
    }

    /**
     * @return list<string>
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function isCache(): bool
    {
        return $this->cache;
    }
}
