<?php

/*
 * This file is part of the Pommx package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pommx\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tree_builder = new TreeBuilder();

        $root_node    = $tree_builder->root('pommx')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->arrayNode('repositories_patterns')
                        ->info('Define mapping between repositories and entities classes.')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('commands')
                        ->addDefaultsIfNotSet()
                        ->info('Configure resources generated from console commands.')
                        ->children()
                            ->arrayNode('psr4')
                                ->addDefaultsIfNotSet()
                                ->info('Classes paths')
                                ->children()
                                    ->scalarNode('directory')
                                        ->info('Base directory. "{$session}" & "{$schema}" can be used as wildcard.')
                                        ->isRequired()
                                        ->defaultValue('%kernel.project_dir%/src/{$session}/Schema/{$schema}')
                                    ->end()
                                    ->scalarNode('namespace')
                                        ->info('Namespace prefix. "{$session}" & "{$schema}" can be used as wildcard.')
                                        ->isRequired()
                                        ->defaultValue('App/{$session}/Schema/{$schema}')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('entity')
                                ->addDefaultsIfNotSet()
                                ->info('Entities configuration.')
                                ->children()
                                    ->scalarNode('directory')
                                        ->info('Entities container namespace/directory.')
                                        ->defaultValue('Entity')
                                    ->end()
                                    ->scalarNode('parent_class')
                                        ->info('Parent class to extends.')
                                        ->defaultValue('Pommx\Entity\AbstractEntity')
                                    ->end()
                                    ->scalarNode('class_suffix')
                                        ->info('Class suffix.')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('repository')
                                ->addDefaultsIfNotSet()
                                ->info('Repositories configuration.')
                                ->children()
                                    ->scalarNode('directory')
                                        ->info('Repositories container namespace/directory.')
                                        ->defaultValue('Repository')
                                    ->end()
                                    ->scalarNode('parent_class')
                                        ->info('Parent class to extends.')
                                        ->defaultValue('Pommx\Repository\AbstractRepository')
                                    ->end()
                                    ->scalarNode('class_suffix')
                                        ->info('Class suffix.')
                                        ->defaultValue('Repository')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('structure')
                                ->addDefaultsIfNotSet()
                                ->info('Structures configuration.')
                                ->children()
                                    ->scalarNode('directory')
                                        ->info('Structures container namespace/directory.')
                                        ->defaultValue('AutoStructure')
                                    ->end()
                                    ->scalarNode('parent_class')
                                        ->info('Parent class to extends.')
                                        ->defaultValue('Pommx\Repository\AbstractRowStructure')
                                    ->end()
                                    ->scalarNode('class_suffix')
                                        ->info('Class suffix.')
                                    ->end()
                                ->end()
                            ->end()
                            ->append($this->getPhinxNode())
                        ->end()
                    ->end()
                    ->append($this->getApiPlatformNode())
                ->end()
            ->end()
        ->end();

        return $tree_builder;
    }

    private function getApiPlatformNode()
    {
        $treeBuilder = new TreeBuilder();

        return $treeBuilder->root('api_platform')
            ->info('API Platform bridge. Add data provider/persister, serializer, pagination extension')
            ->children()
                ->scalarNode('enable')
                    ->info('Enable the bridge.')
                    ->defaultValue(false)
                ->end()
            ->end();
    }

    private function getPhinxNode()
    {
        $treeBuilder = new TreeBuilder();

        return $treeBuilder->root('phinx')
            ->addDefaultsIfNotSet()
            ->info('Phinx bridge. Allow migrations and seeds.')
            ->children()
                ->scalarNode('enable')
                    ->info('Enable the bridge.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('migrations')
                    ->info('Migrations namespace / directory.')
                    ->defaultValue('Migration')
                ->end()
                ->scalarNode('seeds')
                    ->info('Seeds namespace / directory.')
                    ->defaultValue('Seed')
                ->end()
                // ->arrayNode('migrations')
                //     ->info('Migrations configurations.')
                //     ->useAttributeAsKey('name')
                //     ->prototype('scalar')
                //         ->info('Namespace: file path')
                //     ->end()
                // ->end()
                // ->arrayNode('seeds')
                //     ->info('Seeds configurations.')
                //     ->useAttributeAsKey('name')
                //     ->prototype('scalar')
                //         ->info('Namespace: file path')
                //     ->end()
                // ->end()
                ->scalarNode('migration_table')
                    ->info('table used by Phinx to core process.')
                    ->defaultValue('phinxlog')
                ->end()
                ->scalarNode('version_order')
                    ->info('First version name.')
                    ->defaultValue('creation')
                ->end()
            ->end();
    }
}
