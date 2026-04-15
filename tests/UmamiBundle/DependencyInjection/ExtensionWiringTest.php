<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tlb\UmamiBundle\DependencyInjection\TlbUmamiExtension;

final class ExtensionWiringTest extends TestCase
{
    public function testCloudModeRemovesSelfHostedAuthenticatorDefinition(): void
    {
        $container = new ContainerBuilder();
        $extension = new TlbUmamiExtension();

        $extension->load([[
            'mode' => 'cloud',
            'tracker' => ['enabled' => false],
            'api' => [
                'enabled' => true,
                'base_url' => 'https://api-gateway.umami.is',
                'api_key' => 'key',
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('tlb_umami.auth.cloud'));
        self::assertFalse($container->hasDefinition('tlb_umami.auth.self_hosted'));
    }

    public function testSelfHostedModeKeepsSelfHostedAuthenticatorDefinition(): void
    {
        $container = new ContainerBuilder();
        $extension = new TlbUmamiExtension();

        $extension->load([[
            'mode' => 'self_hosted',
            'tracker' => ['enabled' => false],
            'api' => [
                'enabled' => true,
                'base_url' => 'https://analytics.example.com',
                'username' => 'admin',
                'password' => 'secret',
                'cache_pool' => 'cache.app',
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('tlb_umami.auth.cloud'));
        self::assertTrue($container->hasDefinition('tlb_umami.auth.self_hosted'));
        self::assertTrue($container->hasAlias('Tlb\\UmamiBundle\\Auth\\AuthenticatorInterface'));
    }

    public function testTrackerHostUrlIsSuppressedWhenSameAsApiBaseUrl(): void
    {
        $container = new ContainerBuilder();
        $extension = new TlbUmamiExtension();

        $extension->load([[
            'mode' => 'self_hosted',
            'tracker' => [
                'enabled' => true,
                'script_url' => 'https://analytics.example.com/script.js',
                'website_id' => 'website-id',
                'host_url' => 'https://analytics.example.com/',
            ],
            'api' => [
                'enabled' => true,
                'base_url' => 'https://analytics.example.com',
                'username' => 'admin',
                'password' => 'secret',
            ],
        ]], $container);

        $trackerConfigDefinition = $container->getDefinition('tlb_umami.tracker_config');
        self::assertNull($trackerConfigDefinition->getArgument(3));
    }

    public function testTrackerHostUrlIsSuppressedWhenApiDisabledButBaseUrlMatches(): void
    {
        $container = new ContainerBuilder();
        $extension = new TlbUmamiExtension();

        $extension->load([[
            'mode' => 'self_hosted',
            'tracker' => [
                'enabled' => true,
                'script_url' => 'https://analytics.example.com/script.js',
                'website_id' => 'website-id',
                'host_url' => 'https://analytics.example.com',
            ],
            'api' => [
                'enabled' => false,
                'base_url' => 'https://analytics.example.com/',
            ],
        ]], $container);

        $trackerConfigDefinition = $container->getDefinition('tlb_umami.tracker_config');
        self::assertNull($trackerConfigDefinition->getArgument(3));
    }
}
