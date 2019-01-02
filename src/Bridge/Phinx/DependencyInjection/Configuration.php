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

namespace Pommx\Bridge\Phinx\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;

use Pommx\DependencyInjection\BridgeConfigurationInterface;
use Pommx\DependencyInjection\AbstractConfiguration;

use Pommx\Bridge\Phinx\Migration\AbstractMigration;
use Pommx\Bridge\Phinx\Seed\AbstractSeed;

class Configuration extends AbstractConfiguration implements BridgeConfigurationInterface
{
    public const BRIDGE_NAME = 'phinx';

    public function appendConfiguration(TreeBuilder $tree_builder)
    {
        $tree_node = $tree_builder->buildTree();

        // Add main bridge configuration
        $bridge = (new TreeBuilder())->root(self::BRIDGE_NAME)
            ->addDefaultsIfNotSet()
            ->info('Phinx bridge. Allow database migrations and seeds.')
            ->children()
                ->booleanNode('enable')
                    ->info('Enable Phinx bridge.')
                    ->defaultTrue()
                ->end()
            ->end();

        $bridges_node = $this->findNode('bridges', $tree_node);

        $bridges_node->addChild($bridge->getNode(true));

        // Add session bridge configuration
        $sessions_prototype = $this->findNode('sessions', $tree_node)
            ->getPrototype();

        $commands_node = $this->findNode('commands', $sessions_prototype);
        $commands_node->addChild($this->getCommandsPhinxNode());
    }

    private function getCommandsPhinxNode(): NodeInterface
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root(self::BRIDGE_NAME)
            ->addDefaultsIfNotSet()
            ->info('Phinx bridge configuration.')
            ->children()
                ->arrayNode('migrations')
                    ->addDefaultsIfNotSet()
                    ->info('Migrations configuration.')
                    ->children()
                        ->scalarNode('db_table')
                            ->info('Database table used by Phinx to manage migrations.')
                            ->defaultValue('phinxlog')
                        ->end()
                        ->scalarNode('directory')
                            ->info('Migrations container namespace / directory.')
                            ->defaultValue('Migration')
                            ->validate()
                                ->ifString()
                                ->then($this->getReplaceDirectorySeparatorClosure())
                            ->end()
                        ->end()
                        ->scalarNode('parent_class')
                            ->info('Parent class to extends.')
                            ->defaultValue(AbstractMigration::class)
                        ->end()
                        ->scalarNode('class_suffix')
                            ->info('Class suffix.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('seeds')
                    ->addDefaultsIfNotSet()
                    ->info('Seeds configuration.')
                    ->children()
                        ->scalarNode('directory')
                            ->info('Seeds container namespace / directory.')
                            ->defaultValue('Seed')
                            ->validate()
                                ->ifString()
                                ->then($this->getReplaceDirectorySeparatorClosure())
                            ->end()
                        ->end()
                        ->scalarNode('parent_class')
                            ->info('Parent class to extends.')
                            ->defaultValue(AbstractSeed::class)
                        ->end()
                        ->scalarNode('class_suffix')
                            ->info('Class suffix.')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('version_order')
                    ->info('First version name.')
                    ->defaultValue('creation')
                ->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}
