<?php

namespace SN\BackupBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('sn_backup');

        $rootNode->children()
            ->scalarNode('target_fs')->end()
            ->integerNode('timeout')->defaultValue(1800)->end()
            ->arrayNode('databases')->prototype('scalar')->end()->end()
            ->arrayNode('include_fs')->prototype('scalar')->end()->end()
            ->arrayNode('finder')->prototype('array')
                    ->children()
                    ->scalarNode('root_dir')->end()
                    ->arrayNode('in')->prototype('scalar')->end()->end()
                    ->arrayNode('name')->prototype('scalar')->end()->end()
                    ->arrayNode('not_name')->prototype('scalar')->end()->end()
                    ->arrayNode('path')->prototype('scalar')->end()->end()
                    ->arrayNode('size')->prototype('scalar')->end()->end()
                    ->arrayNode('date')->prototype('scalar')->end()->end()
                    ->arrayNode('depth')->prototype('scalar')->end()->end()
                    ->end()
                ->end()->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
