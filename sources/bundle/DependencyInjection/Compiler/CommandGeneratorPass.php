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
use PommX\Console\Command\Configuration;

class CommandGeneratorPass implements DI\Compiler\CompilerPassInterface
{
    /**
     * Checks if defined "pomm_x.commands_generato.configs" are valid.
     * Add repository patterns depending on those configs.
     *
     * @param DIContainerBuilder $container [description]
     */
    public function process(DI\ContainerBuilder $container)
    {
        $existing_configs = $container->getParameter('pomm_x.commands_generator.existing_configs');
        $configs          = $container->getParameter('pomm_x.commands_generator.configuration');

        $diff = array_diff(array_keys($configs['configs'] ?? []), $existing_configs);

        if (false == empty($diff)) {
            throw new \LogicException(
                sprintf(
                    'PommX extension configuration failed.'.PHP_EOL
                    .'"pomm_x.commands_generator.configs" is invalid.'.PHP_EOL
                    .'{"%s"} doesn\'t exists as Pomm configuration.',
                    join('", "', $diff)
                )
            );
        }

        $patterns = [];
        foreach ($existing_configs as $config_name) {
            $entity_conf  = new Configuration($configs, $config_name, 'entity');
            $repo_conf    = new Configuration($configs, $config_name, 'repository');

            $entity_infos = $entity_conf->getFileInfos('{$schema}', '{$relation}');
            $repo_infos   = $repo_conf->getFileInfos('{$schema}', '{$relation}');

            $patterns[$entity_infos['name']] = $repo_infos['name'];
        }

        $defined_patterns = $container->getParameter('pomm_x.repositories_patterns');
        $patterns = array_merge($defined_patterns, $patterns);
        $container->setParameter('pomm_x.repositories_patterns', $patterns);
    }
}
