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

namespace PommX\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;

use PommX\Bundle\DependencyInjection\Configuration;
use PommX\Session\SessionBuilder;

class PommXExtension extends Extension implements PrependExtensionInterface
{

    /**
     * Add PommX session builder as default one.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        // Get all bundles
        $bundles = $container->getParameter('kernel.bundles');

        // Determine if PommBundle is registered
        if (true == isset($bundles['PommBundle'])
            && false == is_null($extension = $container->getExtensions()['pomm'] ?? null)
        ) {
            // Define the session builder and the config as the default one
            $configurations = [
              'pomm_x' => [
                'dsn'             => "%env(DATABASE_URL)%",
                'session_builder' => SessionBuilder::class,
                'pomm:default'    => true
              ]
            ];

            $existing_configs = [];
            foreach ($container->getExtensionConfig($extension->getAlias()) as $config) {
                $existing_configs = \array_merge(
                    $existing_configs,
                    array_keys($config['configuration'] ?? [])
                );
            }

            $container->setParameter(
                'pomm_x.commands_generator.existing_configs',
                $existing_configs
            );

            // Add config to Pomm extension
            $container->prependExtensionConfig('pomm', ['configuration' => $configurations]);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services/pomm_x.yaml');

        $conf = new Configuration();
        // $dumper = new YamlReferenceDumper();
        // var_dump($dumper->dump($conf));
        $config = $this->processConfiguration($conf, $configs);

        $this->configure($config, $container);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configure(array $config, ContainerBuilder $container)
    {
        $container->setParameter('pomm_x.repositories_patterns', $config['repositories_patterns']);
        $container->setParameter('pomm_x.commands_generator.configuration', $config['commands_generator']);
    }
}
