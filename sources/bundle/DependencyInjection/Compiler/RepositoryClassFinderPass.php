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

use Symfony\Component\DependencyInjection as DI;

use PommX\Tools\RepositoryClassFinder;

class RepositoryClassFinderPass implements DI\Compiler\CompilerPassInterface
{
    /**
     * Add repository patterns.
     *
     * @param  DIContainerBuilder $container [description]
     */
    public function process(DI\ContainerBuilder $container)
    {
        $config     = $container->getParameter('pomm_x.repositories_patterns');
        $definition = $container->getDefinition(RepositoryClassFinder::class);

        foreach ($config as $entity_pattern => $repository_pattern) {
            $definition->addMethodCall('addPattern', [$entity_pattern, $repository_pattern]);
        }
    }
}
