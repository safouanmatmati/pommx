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
use Pommx\Console\Command\Configuration;

class CommandGeneratorPass implements CompilerPassInterface
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
     * Add commands configurations to each Pommx commands concerned.
     * Add repositories patterns depending on thoses configurations.
     *
     * @param ContainerBuilder $container [description]
     */
    public function process(ContainerBuilder $container)
    {
        $commands_generator_confs = $patterns = [];

        foreach ($this->extension->getConfs() as $name => $data) {
            $conf = $data;

            // Isolate commands configs
            $commands_by_sessions[$name] = $data['commands'];

            // Define related repositories patterns
            $entity_conf  = new Configuration($commands_by_sessions, $name, 'entity');
            $repo_conf    = new Configuration($commands_by_sessions, $name, 'repository');

            $entity_infos = $entity_conf->getFileInfos('{$schema}', '{$relation}');
            $repo_infos   = $repo_conf->getFileInfos('{$schema}', '{$relation}');

            $conf['repositories_patterns'] = ($conf['repositories_patterns'] ?? []) + [
                $entity_infos['name'] => $repo_infos['name']
            ];

            $this->extension->setConf($name, $conf);
        }

        // Add configurations to each Pommx commands concerned
        foreach ($container->findTaggedServiceIds('pommx.command') as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->addMethodCall('setConfigurations', [$commands_by_sessions]);
        }
    }
}
