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

use PommProject\Cli\Command\PommAwareCommand;

use Pommx\DependencyInjection\Compiler\AbstractPass;
use Pommx\Console\Command\Configuration;
use Pommx\Console\Command\PommxAwareCommandInterface;

class CommandsPass extends AbstractPass implements CompilerPassInterface
{
    /**
     * Add commands configurations to each Pommx commands concerned.
     * Add repositories patterns depending on thoses configurations.
     *
     * @param ContainerBuilder $container [description]
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->getPommxExtension()->getSessions() as $name => $data) {
            $conf = $data;

            // Isolate commands configs
            $commands[$name] = $data['commands'];

            // Define related repositories patterns
            $entity_conf  = new Configuration($commands, $name, 'entity');
            $repo_conf    = new Configuration($commands, $name, 'repository');

            $entity_infos = $entity_conf->getFileInfos('{$schema}', '{$relation}');
            $repo_infos   = $repo_conf->getFileInfos('{$schema}', '{$relation}');

            $conf['repositories_patterns'] = ($conf['repositories_patterns'] ?? []) + [
                $entity_infos['name'] => $repo_infos['name']
            ];

            $this->getPommxExtension()->setSessions($name, $conf);
        }

        $pomm_commands_option = $this->getPommxExtension()->getPommConfiguration()['commands'];
        $pomm_commands = $replaced = [];

        foreach ($container->findTaggedServiceIds('console.command') as $id => $tags) {
             $definition = $container->getDefinition($id);

            if (true == is_subclass_of($definition->getClass(), PommAwareCommand::class)) {
                if (true == is_subclass_of($definition->getClass(), PommxAwareCommandInterface::class)) {
                   // Add configurations to each Pommx commands concerned
                   $definition->addMethodCall('setConfigurations', [$commands]);

                   if (true !== $pomm_commands_option['replace']) {
                       continue;
                   }

                   foreach ($definition->getMethodCalls() as $method_definition) {
                       if ($method_definition[0] == 'setReplacedCommandClass') {
                           $replaced = array_merge($replaced, $method_definition[1]);
                       }
                   }

                   continue;
               }

               // Remove Pomm console command
               if (false == $pomm_commands_option['enable']) {
                  $container->removeDefinition($id);
                  continue;
               }

               // Keep definition trace
               $pomm_commands[$id] = $definition->getClass();
            }
        }

        // Remove Pomm console commands that are replaced by a Pommx console command
        if (true == $pomm_commands_option['replace']) {
            foreach (array_intersect($pomm_commands, $replaced) as $id => $class) {
               $container->removeDefinition($id);
            }
        }
    }
}
