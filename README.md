# TlbUmamiBundle

`TlbUmamiBundle` integrates [Umami](https://umami.is/) analytics into Symfony applications.

It supports:
- Twig script rendering (`umami_script()`)
- Server-side event sending (`/api/send`)
- Reporting API calls for selected Umami v3 endpoints
- Both Umami Cloud API key auth and self-hosted username/password auth

## Requirements

- PHP 8.1+
- Symfony 6.4+
- Umami v3

Intended package name: `tlb/umami-bundle`.

## Installation

### 1) Install package

```bash
composer require tlb/umami-bundle
```

### 2) Register bundle

In `config/bundles.php`:

```php
<?php

return [
    // ...
    Tlb\UmamiBundle\TlbUmamiBundle::class => ['all' => true],
];
```

## Configuration

Create `config/packages/umami.yaml`:

```yaml
umami:
  mode: self_hosted

  tracker:
    enabled: false
    script_url: '%env(string:UMAMI_TRACKER_SCRIPT_URL)%'
    website_id: '%env(string:UMAMI_TRACKER_WEBSITE_ID)%'
    host_url: '%env(string:UMAMI_TRACKER_HOST_URL)%'
    auto_track: '%env(bool:UMAMI_TRACKER_AUTO_TRACK)%'
    do_not_track: '%env(bool:UMAMI_TRACKER_DO_NOT_TRACK)%'
    domains: '%env(string:UMAMI_TRACKER_DOMAINS)%'
    tag: '%env(string:UMAMI_TRACKER_TAG)%'
    cache: '%env(bool:UMAMI_TRACKER_CACHE)%'

  api:
    enabled: false
    base_url: ''
    username: null
    password: null
    api_key: null
    cache_pool: null
```

Add env defaults:

```dotenv
UMAMI_TRACKER_SCRIPT_URL=
UMAMI_TRACKER_WEBSITE_ID=
UMAMI_BASE_URL=
UMAMI_TRACKER_HOST_URL=${UMAMI_BASE_URL}
UMAMI_TRACKER_AUTO_TRACK=true
UMAMI_TRACKER_DO_NOT_TRACK=false
UMAMI_TRACKER_DOMAINS=
UMAMI_TRACKER_TAG=
UMAMI_TRACKER_CACHE=false
UMAMI_API_BASE_URL=${UMAMI_BASE_URL}
UMAMI_API_USERNAME=
UMAMI_API_PASSWORD=
UMAMI_API_KEY=
UMAMI_API_CACHE_POOL=cache.app
```

Then configure real values in `.env.local` or deployment environment variables.

`UMAMI_TRACKER_DOMAINS` supports both comma-separated values (`example.com,www.example.com`) and JSON array format (`["example.com","www.example.com"]`).
Switch feature flags in YAML:
- set `umami.mode: cloud` for cloud authentication
- set `umami.tracker.enabled: true` to render `umami_script()`
- set `umami.api.enabled: true` to register API client/event sender services

Example values (prefer setting these in `.env.local`):

### Self-hosted Umami

```dotenv
UMAMI_BASE_URL=https://analytics.example.com
UMAMI_TRACKER_SCRIPT_URL=${UMAMI_BASE_URL}/script.js
UMAMI_TRACKER_WEBSITE_ID=your-website-id
UMAMI_API_USERNAME=admin
UMAMI_API_PASSWORD=change-me
```

### Umami Cloud

```dotenv
UMAMI_BASE_URL=https://api-gateway.umami.is
UMAMI_TRACKER_SCRIPT_URL=https://cloud.umami.is/script.js
UMAMI_TRACKER_WEBSITE_ID=your-website-id
UMAMI_API_KEY=your-cloud-api-key
```

## Usage

### Twig tracking script

Render the configured script tag:

```twig
{{ umami_script() }}
```

Override selected attributes at render time:

```twig
{{ umami_script({
  'data-tag': 'marketing',
  'data-auto-track': 'false'
}) }}
```

### API client

Inject `Tlb\UmamiBundle\Client\UmamiApiClient`:

```php
<?php

use Tlb\UmamiBundle\Client\UmamiApiClient;

final class AnalyticsService
{
    public function __construct(private readonly UmamiApiClient $umamiApiClient)
    {
    }

    public function summary(string $websiteId, int $startAt, int $endAt): array
    {
        return $this->umamiApiClient->getStats($websiteId, $startAt, $endAt);
    }
}
```

Supported endpoints:
- `/api/websites/{id}/stats`
- `/api/websites/{id}/pageviews`
- `/api/websites/{id}/events/stats`

### Event sender

Inject `Tlb\UmamiBundle\Event\UmamiEventSender`:

```php
$umamiEventSender->send(
    websiteId: 'website-id',
    url: 'https://example.com/pricing',
    hostname: 'example.com',
    eventName: 'cta_click',
    tag: 'marketing',
    data: ['plan' => 'pro'],
);
```

## Authentication behavior

- `cloud` mode: sends `x-umami-api-key` header.
- `self_hosted` mode: authenticates via `/api/auth/login`, stores bearer token, and can cache token in configured cache pool.
- On `401`/`403` from API requests, auth is invalidated and one re-auth/retry is attempted.

## Development

Run tests:

```bash
php bin/phpunit
```

## Symfony Flex recipe template

A ready-to-use recipe template is included at:

- `recipes/tlb/umami-bundle/1.0/manifest.json`
- `recipes/tlb/umami-bundle/1.0/config/packages/umami.yaml`
- `recipes/tlb/umami-bundle/1.0/.env`
