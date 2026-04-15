<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('umami');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('mode')
                    ->values(['self_hosted', 'cloud'])
                    ->defaultValue('self_hosted')
                ->end()
                ->arrayNode('tracker')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('script_url')->defaultNull()->end()
                        ->scalarNode('website_id')->defaultNull()->end()
                        ->scalarNode('host_url')->defaultNull()->end()
                        ->booleanNode('auto_track')->defaultTrue()->end()
                        ->booleanNode('do_not_track')->defaultFalse()->end()
                        ->variableNode('domains')
                            ->defaultValue([])
                        ->end()
                        ->scalarNode('tag')->defaultNull()->end()
                        ->booleanNode('cache')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('base_url')->defaultNull()->end()
                        ->scalarNode('username')->defaultNull()->end()
                        ->scalarNode('password')->defaultNull()->end()
                        ->scalarNode('api_key')->defaultNull()->end()
                        ->scalarNode('cache_pool')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(static fn (array $config): bool => true === $config['tracker']['enabled'] && (
                    null === $config['tracker']['script_url']
                    || null === $config['tracker']['website_id']
                    || '' === trim((string) $config['tracker']['script_url'])
                    || '' === trim((string) $config['tracker']['website_id'])
                ))
                ->thenInvalid('When umami.tracker.enabled is true, both tracker.script_url and tracker.website_id are required.')
            ->end()
            ->validate()
                ->ifTrue(static fn (array $config): bool => true === $config['api']['enabled'] && (
                    null === $config['api']['base_url'] || '' === trim((string) $config['api']['base_url'])
                ))
                ->thenInvalid('When umami.api.enabled is true, api.base_url is required.')
            ->end()
            ->validate()
                ->ifTrue(static fn (array $config): bool => true === $config['api']['enabled']
                    && 'cloud' === $config['mode']
                    && (null === $config['api']['api_key'] || '' === trim((string) $config['api']['api_key'])))
                ->thenInvalid('When umami.mode is cloud and api is enabled, api.api_key is required.')
            ->end()
            ->validate()
                ->ifTrue(static fn (array $config): bool => true === $config['api']['enabled']
                    && 'self_hosted' === $config['mode']
                    && (
                        null === $config['api']['username']
                        || null === $config['api']['password']
                        || '' === trim((string) $config['api']['username'])
                        || '' === trim((string) $config['api']['password'])
                    ))
                ->thenInvalid('When umami.mode is self_hosted and api is enabled, api.username and api.password are required.')
            ->end();

        return $treeBuilder;
    }
}
