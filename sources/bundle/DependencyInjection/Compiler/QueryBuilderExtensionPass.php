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

namespace PommX\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use PommX\Repository\QueryBuilder\Extension\ExtensionsManager;

class QueryBuilderExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // find all service IDs with the appropriate tag
        $tagged_services = $container->findTaggedServiceIds('pommx.query_builder_extension');
        $definition      = $container->getDefinition(ExtensionsManager::class);

        foreach (array_keys($tagged_services) as $id) {
            $definition->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
