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

use Pommx\DependencyInjection\AbstractConfiguration;

use Pommx\Bridge\Phinx\Migration\AbstractMigration;
use Pommx\Bridge\Phinx\Seed\AbstractSeed;

use Pommx\Repository\AbstractRepository;
use Pommx\Repository\AbstractRowStructure;
use Pommx\Entity\AbstractEntity;

class Configuration extends AbstractConfiguration implements ConfigurationInterface
{
    /**
     * [private description]
     * @var array
     */
    private $bridges;

    public function __construct(array $bridges = [])
    {
        $this->bridges = $bridges;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tree_builder = new TreeBuilder();

        $root_node    = $tree_builder->root('pommx')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('pomm')
                    ->addDefaultsIfNotSet()
                    ->info('Pomm general configuration')
                    ->children()
                        ->arrayNode('commands')
                            ->addDefaultsIfNotSet()
                            ->info('Pomm console commands configuration')
                            ->children()
                                ->booleanNode('enable')
                                    ->info('Enable / disable Pomm console commands.')
                                    ->defaultTrue()
                                ->end()
                                ->booleanNode('replace')
                                    ->info('Hide Pomm console commands replaced by those from Pommx.')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('sessions')
                    ->info('Sessions configuration.')
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
                                                ->info('Base directory. "{$config_name}" & "{$schema}" can be used as wildcard.')
                                                ->isRequired()
                                                ->defaultValue(join(DIRECTORY_SEPARATOR, ['%kernel.project_dir%','src', '{$config_name}', 'Schema', '{$schema}']))
                                                ->validate()
                                                    ->ifString()
                                                    ->then($this->getReplaceDirectorySeparatorClosure())
                                                ->end()
                                            ->end()
                                            ->scalarNode('namespace')
                                                ->info('Namespace prefix. "{$config_name}" & "{$schema}" can be used as wildcard.')
                                                ->isRequired()
                                                ->defaultValue('App\{$config_name}\Schema\{$schema}')
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
                                                ->validate()
                                                    ->ifString()
                                                    ->then($this->getReplaceDirectorySeparatorClosure())
                                                ->end()
                                            ->end()
                                            ->scalarNode('parent_class')
                                                ->info('Parent class to extends.')
                                                ->defaultValue(AbstractEntity::class)
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
                                                ->validate()
                                                    ->ifString()
                                                    ->then($this->getReplaceDirectorySeparatorClosure())
                                                ->end()
                                            ->end()
                                            ->scalarNode('parent_class')
                                                ->info('Parent class to extends.')
                                                ->defaultValue(AbstractRepository::class)
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
                                                ->validate()
                                                    ->ifString()
                                                    ->then($this->getReplaceDirectorySeparatorClosure())
                                                ->end()
                                            ->end()
                                            ->scalarNode('parent_class')
                                                ->info('Parent class to extends.')
                                                ->defaultValue(AbstractRowStructure::class)
                                            ->end()
                                            ->scalarNode('class_suffix')
                                                ->info('Class suffix.')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        $tree_builder = $this->appendBridgesNode($tree_builder);

        return $tree_builder;
    }

    private function appendBridgesNode(TreeBuilder $tree_builder): TreeBuilder
    {
        $tree_builder->buildTree()->addChild(
            (new TreeBuilder())->root('bridges')
                ->addDefaultsIfNotSet()
                ->info('Bridges configurations')
                ->end()
                ->buildTree()
        );

        foreach ($this->bridges as $bridge) {
            $bridge->appendConfiguration($tree_builder);
        }

        return $tree_builder;
    }
}
