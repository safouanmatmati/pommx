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

use Pommx\DependencyInjection\Compiler\AbstractPass;
use Pommx\Tools\RepositoryClassFinder;

class RepositoryClassFinderPass extends AbstractPass implements CompilerPassInterface
{
    /**
     * Add repository patterns.
     *
     * @param  ContainerBuilder $container [description]
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition(RepositoryClassFinder::class);

        foreach ($this->getPommxExtension()->getSessions() as $key => $config) {
            foreach (($config['repositories_patterns'] ?? []) as $entity_pattern => $repository_pattern) {
                $definition->addMethodCall('addPattern', [$entity_pattern, $repository_pattern]);
            }
        }
    }
}
