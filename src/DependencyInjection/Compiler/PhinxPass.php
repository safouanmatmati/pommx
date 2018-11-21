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

namespace Pommx\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Pommx\DependencyInjection\PommxExtension;

class PhinxPass implements CompilerPassInterface
{
    /**
     * Pommx extension.
     *
     * @var PommxExtension
     */
    private $extension;

    /**
     * [__construct description]
     * @param PommxExtension $extension [description]
     */
    public function __construct(PommxExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * Add repository patterns.
     *
     * @param  ContainerBuilder $container [description]
     */
    public function process(ContainerBuilder $container)
    {
        $confs = [];

        foreach ($this->extension->getConfs() as $name => $data) {
            if (true !== ($data['commands']['phinx']['enable'] ?? false)) {
                continue;
            }

            $data = $data['commands']['phinx'];

            $formatted = [
               'psr4'            => $data['psr4'],
               'migrations'      => $data['migrations'],
               'seeds'           => $data['seeds'],
               'migration_table' => $data['migration_table'],
               'version_order'   => $data['version_order'],
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
