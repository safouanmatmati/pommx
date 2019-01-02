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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Pommx\DependencyInjection\Compiler\AbstractPass;
use Pommx\Bridge\Phinx\DependencyInjection\Configuration;
use Pommx\Bridge\Phinx\Migration\AbstractMigration;
use Pommx\Bridge\Phinx\Seed\AbstractSeed;

class Pass extends AbstractPass
{
    /**
     * Set Phinx bridge console commands configurations
     *
     * @param  ContainerBuilder $container [description]
     */
    public function process(ContainerBuilder $container)
    {
        if (false == $this->getPommxExtension()->getBridgeConfiguration(Configuration::BRIDGE_NAME)['enable']) {
            return;
        }

        $confs = [];

        foreach ($this->getPommxExtension()->getSessions() as $name => $data) {
            $phinx_data = $data['commands'][Configuration::BRIDGE_NAME];

            $migration_parent_class = $phinx_data['migrations']['parent_class'];

            if ($migration_parent_class !== AbstractMigration::class) {
                if (false === is_subclass_of($migration_parent_class, AbstractMigration::class)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            '"%s" class have to be a subclass of "%s" class ',
                            $migration_parent_class,
                            AbstractMigration::class
                        )
                    );
                }
            }

            $seed_parent_class = $phinx_data['seeds']['parent_class'];

            if ($seed_parent_class !== AbstractSeed::class) {
                if (false === is_subclass_of($seed_parent_class, AbstractSeed::class)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            '"%s" class have to be a subclass of "%s" class ',
                            $seed_parent_class,
                            AbstractSeed::class
                        )
                    );
                }
            }

            $formatted = [
               'psr4'            => $data['commands']['psr4'],
               'migrations'      => $phinx_data['migrations'],
               'seeds'           => $phinx_data['seeds'],
               'migration_table' => $phinx_data['migrations']['db_table'],
               'version_order'   => $phinx_data['version_order'],
            ];

            $phinx_by_pommx_conf[$name] = $formatted;
        }

        // Add configurations to each Pommx commands concerned
        foreach ($container->findTaggedServiceIds('pommx.phinx.command') as $id => $tags) {
            $definition = $container->getDefinition($id);

            $definition->addMethodCall('setPommxConfigs', [new Reference('pomm'), $phinx_by_pommx_conf]);
        }
    }
}
