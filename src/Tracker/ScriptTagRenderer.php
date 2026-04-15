<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tracker;

final class ScriptTagRenderer
{
    public function __construct(private readonly TrackerConfig $config)
    {
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function render(array $overrides = []): string
    {
        if (!$this->config->isEnabled()) {
            return '';
        }

        $attributes = [
            'defer' => true,
            'src' => $this->config->getScriptUrl(),
            'data-website-id' => $this->config->getWebsiteId(),
            'data-host-url' => $this->shouldRenderHostUrl(),
            // Omit values that match Umami defaults to keep output minimal.
            'data-auto-track' => $this->config->isAutoTrack() ? null : 'false',
            'data-do-not-track' => $this->config->isDoNotTrack() ? 'true' : null,
            'data-domains' => [] !== $this->config->getDomains() ? implode(',', $this->config->getDomains()) : null,
            'data-tag' => $this->config->getTag(),
            'data-cache' => $this->config->isCache() ? 'true' : null,
        ];

        foreach ($this->allowedOverrideKeys() as $key) {
            if (\array_key_exists($key, $overrides)) {
                $attributes[$key] = $this->normalizeOverrideValue($key, $overrides[$key]);
            }
        }

        $parts = [];
        foreach ($attributes as $key => $value) {
            if (null === $value || false === $value || '' === $value) {
                continue;
            }

            if (true === $value) {
                $parts[] = $key;

                continue;
            }

            $parts[] = sprintf('%s="%s"', $key, htmlspecialchars((string) $value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'));
        }

        return sprintf('<script %s></script>', implode(' ', $parts));
    }

    /**
     * @return list<string>
     */
    private function allowedOverrideKeys(): array
    {
        return [
            'defer',
            'src',
            'data-website-id',
            'data-host-url',
            'data-auto-track',
            'data-do-not-track',
            'data-domains',
            'data-tag',
            'data-cache',
        ];
    }

    private function normalizeOverrideValue(string $key, mixed $value): mixed
    {
        if (!\in_array($key, ['data-auto-track', 'data-do-not-track', 'data-cache'], true)) {
            return $value;
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }

    private function shouldRenderHostUrl(): ?string
    {
        $hostUrl = $this->config->getHostUrl();
        if (null === $hostUrl || '' === trim($hostUrl)) {
            return null;
        }

        $normalizedHost = rtrim(trim($hostUrl), '/');
        $scriptOrigin = $this->extractOrigin($this->config->getScriptUrl());
        if (null !== $scriptOrigin && $normalizedHost === $scriptOrigin) {
            return null;
        }

        return $hostUrl;
    }

    private function extractOrigin(string $url): ?string
    {
        $parts = parse_url($url);
        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = sprintf('%s://%s', $parts['scheme'], $parts['host']);
        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return rtrim($origin, '/');
    }
}
