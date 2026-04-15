<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Tlb\UmamiBundle\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    private Processor $processor;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function testCloudModeRequiresApiKeyWhenApiEnabled(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([
            'mode' => 'cloud',
            'api' => [
                'enabled' => true,
                'base_url' => 'https://api-gateway.umami.is',
            ],
        ]);
    }

    public function testSelfHostedRequiresCredentialsWhenApiEnabled(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([
            'mode' => 'self_hosted',
            'api' => [
                'enabled' => true,
                'base_url' => 'https://umami.example.com',
            ],
        ]);
    }

    public function testTrackerRequiresScriptAndWebsiteIdWhenEnabled(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([
            'tracker' => [
                'enabled' => true,
                'script_url' => 'https://analytics.example.com/script.js',
            ],
        ]);
    }

    public function testValidCloudConfigPasses(): void
    {
        $config = $this->process([
            'mode' => 'cloud',
            'tracker' => [
                'enabled' => true,
                'script_url' => 'https://analytics.umami.is/script.js',
                'website_id' => 'website-1',
            ],
            'api' => [
                'enabled' => true,
                'base_url' => 'https://api-gateway.umami.is',
                'api_key' => 'secret',
            ],
        ]);

        self::assertSame('cloud', $config['mode']);
        self::assertTrue($config['api']['enabled']);
        self::assertSame('secret', $config['api']['api_key']);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return $this->processor->processConfiguration($this->configuration, [$config]);
    }
}
