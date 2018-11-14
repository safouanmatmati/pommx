<?php

/*
 * This file is part of the PommX package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PommX\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;

class Configuration implements ConfigurationInterface
{
    private function addBasicsCommandsNodes(NodeParentInterface $node)
    {
        $node
            ->append(new ScalarNodeDefinition('root_ns'))
            ->append(new ScalarNodeDefinition('root_dir'))
            ->append(new ScalarNodeDefinition('schema_dir'))
            ->append(new ScalarNodeDefinition('dir'));

        return $node;
    }

    private function addSpecificCommandsNode(NodeParentInterface $node, string $child_name)
    {
        $child_node = (new ArrayNodeDefinition($child_name))
            ->append(new ScalarNodeDefinition('parent_class'))
            ->append(new ScalarNodeDefinition('class_suffix'))
            ->append(new ScalarNodeDefinition('dir'));

        $node->append($child_node);
        return $node;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tree_builder = new TreeBuilder();
        $root_node    = $tree_builder->root('pommx');

        $root_node
            ->children()
            ->arrayNode('repositories_patterns')
            ->useAttributeAsKey('name')
            ->prototype('scalar')->end()
            ->end();

        $cmd_node = (new ArrayNodeDefinition('commands_generator'));
        $cmd_configs_node = (new ArrayNodeDefinition('configs'))
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->end();

        foreach (array_merge([$cmd_node], $cmd_configs_node->getChildNodeDefinitions()) as $node) {
            $node->append(new ScalarNodeDefinition('config_alias'));
            $this->addBasicsCommandsNodes($node);
            $this->addSpecificCommandsNode($node, 'entity');
            $this->addSpecificCommandsNode($node, 'structure');
            $this->addSpecificCommandsNode($node, 'repository');
        }

        $cmd_node->append($cmd_configs_node);
        $root_node->append($cmd_node);

        return $tree_builder;
    }
}
