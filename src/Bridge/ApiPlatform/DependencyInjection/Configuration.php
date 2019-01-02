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

namespace Pommx\Bridge\ApiPlatform\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use Pommx\DependencyInjection\BridgeConfigurationInterface;
use Pommx\DependencyInjection\AbstractConfiguration;

use Pommx\Bridge\Phinx\Migration\AbstractMigration;
use Pommx\Bridge\Phinx\Seed\AbstractSeed;

class Configuration extends AbstractConfiguration implements BridgeConfigurationInterface
{
    public const BRIDGE_NAME = 'api_platform';

    public function appendConfiguration(TreeBuilder $tree_builder)
    {
        foreach ($tree_builder->buildTree()->getChildren() as $name => $node) {
            if ($name != 'bridges') {
                continue;
            }

            $bridge = (new TreeBuilder())->root(self::BRIDGE_NAME)
                ->addDefaultsIfNotSet()
                ->info('API Platform bridge. Add data provider/persister, serializer, pagination extension')
                ->children()
                    ->scalarNode('enable')
                        ->info('Enable the bridge.')
                        ->defaultValue(false)
                    ->end()
                ->end();

            $node->addChild($bridge->getNode(true));

            break;
        }
    }
}
