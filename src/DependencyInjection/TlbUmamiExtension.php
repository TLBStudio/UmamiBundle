<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Tlb\UmamiBundle\Api\ApiConfig;
use Tlb\UmamiBundle\Auth\AuthenticatorInterface;
use Tlb\UmamiBundle\Tracker\TrackerConfig;
use Tlb\UmamiBundle\Twig\UmamiExtension;

final class TlbUmamiExtension extends Extension
{
    public function getAlias(): string
    {
        return 'umami';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $trackerHostUrl = $this->normalizeTrackerHostUrl(
            $config['tracker']['host_url'],
            $config['api']['base_url'] ?? null,
        );

        $container->setDefinition('tlb_umami.tracker_config', new Definition(TrackerConfig::class, [
            $config['tracker']['enabled'],
            (string) ($config['tracker']['script_url'] ?? ''),
            (string) ($config['tracker']['website_id'] ?? ''),
            $trackerHostUrl,
            $config['tracker']['auto_track'],
            $config['tracker']['do_not_track'],
            $this->normalizeDomains($config['tracker']['domains'] ?? []),
            $config['tracker']['tag'],
            $config['tracker']['cache'],
        ]));
        $container->setAlias(TrackerConfig::class, 'tlb_umami.tracker_config')->setPublic(false);

        if (!$config['api']['enabled']) {
            $container->removeDefinition('tlb_umami.api_config');
            $container->removeDefinition('tlb_umami.auth.cloud');
            $container->removeDefinition('tlb_umami.auth.self_hosted');
            $container->removeDefinition('tlb_umami.client');
            $container->removeDefinition('tlb_umami.event_sender');
            if ($container->hasAlias(AuthenticatorInterface::class)) {
                $container->removeAlias(AuthenticatorInterface::class);
            }

            return;
        }

        $container->setDefinition('tlb_umami.api_config', new Definition(ApiConfig::class, [
            true,
            $config['mode'],
            (string) $config['api']['base_url'],
            $config['api']['username'],
            $config['api']['password'],
            $config['api']['api_key'],
        ]));
        $container->setAlias(ApiConfig::class, 'tlb_umami.api_config')->setPublic(false);

        if ('cloud' === $config['mode']) {
            $container->removeDefinition('tlb_umami.auth.self_hosted');
            $container->setAlias(AuthenticatorInterface::class, new Alias('tlb_umami.auth.cloud', false));
        } else {
            $container->removeDefinition('tlb_umami.auth.cloud');
            $container->setAlias(AuthenticatorInterface::class, new Alias('tlb_umami.auth.self_hosted', false));

            if (\is_string($config['api']['cache_pool']) && '' !== $config['api']['cache_pool']) {
                $container->getDefinition('tlb_umami.auth.self_hosted')
                    ->setArgument('$cachePool', new Reference($config['api']['cache_pool']));
            } else {
                $container->getDefinition('tlb_umami.auth.self_hosted')
                    ->setArgument('$cachePool', null);
            }
        }

        if (!$container->hasAlias(UmamiExtension::class) && $container->hasDefinition('tlb_umami.twig.extension')) {
            $container->setAlias(UmamiExtension::class, 'tlb_umami.twig.extension')->setPublic(false);
        }
    }

    /**
     * @param mixed $rawDomains
     *
     * @return list<string>
     */
    private function normalizeDomains(mixed $rawDomains): array
    {
        if (\is_array($rawDomains)) {
            $domains = array_filter(array_map(static fn (mixed $domain): string => trim((string) $domain), $rawDomains));

            return array_values($domains);
        }

        if (!\is_string($rawDomains) || '' === trim($rawDomains)) {
            return [];
        }

        $trimmed = trim($rawDomains);
        if (str_starts_with($trimmed, '[')) {
            /** @var mixed $decoded */
            $decoded = json_decode($trimmed, true);
            if (\is_array($decoded)) {
                $domains = array_filter(array_map(static fn (mixed $domain): string => trim((string) $domain), $decoded));

                return array_values($domains);
            }
        }

        $domains = array_filter(array_map(static fn (string $domain): string => trim($domain), explode(',', $trimmed)));

        return array_values($domains);
    }

    private function normalizeTrackerHostUrl(mixed $trackerHostUrl, mixed $apiBaseUrl): ?string
    {
        if (!\is_string($trackerHostUrl) || '' === trim($trackerHostUrl)) {
            return null;
        }

        $hostUrl = trim($trackerHostUrl);
        if (!\is_string($apiBaseUrl) || '' === trim($apiBaseUrl)) {
            return $hostUrl;
        }

        $normalizedHost = rtrim($hostUrl, '/');
        $normalizedBase = rtrim(trim($apiBaseUrl), '/');

        if ($normalizedHost === $normalizedBase) {
            return null;
        }

        return $hostUrl;
    }
}
