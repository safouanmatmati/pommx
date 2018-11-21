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

namespace Pommx\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;

use Pommx\DependencyInjection\Configuration;
use Pommx\Session\SessionBuilder;

class PommxExtension extends Extension implements PrependExtensionInterface
{
    /**
     * [$pommx_config_names description]
     *
     * @var array
     */
    private $pommx_config_names;

    /**
     * [$extension_config description]
     *
     * @var array
     */
    private $extension_config;

    /**
     * Add Pommx session builder if no one exists.
     * Set it as default Pomm session if there no default one.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        // Get all bundles
        $bundles = $container->getParameter('kernel.bundles');

        // Determine if Pomm bundle is registered
        if (true == isset($bundles['PommBundle'])
            && false == is_null($extension = $container->getExtensions()['pomm'])
        ) {
            $define_pommx_session = $define_as_default = true;

            foreach ($container->getExtensionConfig($extension->getAlias()) as $extension) {
                foreach ($extension['configuration'] as $name => $config) {
                    $session_builder = $config['session_builder'] ?? null;

                    // A Pommx builer session is already defined
                    if (false == is_null($session_builder)
                        && (true == is_subclass_of($session_builder, SessionBuilder::class)
                        || $session_builder == SessionBuilder::class)
                    ) {
                        // Do not create another one.
                        $define_pommx_session = false;

                        // Register it as a Pommx builder session
                        $this->pommx_config_names[] = $name;
                        continue;
                    }

                    // There is already a default Pomm session
                    if (true == ($config['pomm:default'] ?? null)) {
                        $define_as_default = false;
                    }
                }
            }

            // Define a Pommx session builder
            if (true == $define_pommx_session) {
                $configurations = [
                  'pommx.db' => [
                    'dsn'             => "%env(DATABASE_URL)%",
                    'session_builder' => SessionBuilder::class,
                    'pomm:default'    => $define_as_default
                  ]
                ];

                $this->pommx_config_names[] = 'pommx.db';

                // Add it to Pomm extension
                $container->prependExtensionConfig('pomm', ['configuration' => $configurations]);
            }
        } else {
            throw new \LogicException(
                'Failed to initialize Pommx bundle.'
                .'It require Pomm bundle to be registered first.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services/commands.yaml');
        $loader->load('services/entity.yaml');
        $loader->load('services/property_info.yaml');
        $loader->load('services/repository.yaml');
        $loader->load('services/serializer.yaml');
        $loader->load('services/session.yaml');
        $loader->load('services/tools.yaml');

        $conf = new Configuration();

        // $dumper = new YamlReferenceDumper();
        // var_dump($dumper->dump($conf));die(__METHOD__);

        $config = $this->processConfiguration($conf, $configs);

        foreach ($config as $configuration) {
            if (true === ($configuration['api_platform']['enable'] ?? false)) {
                $loader->load('services/bridges/api_platform.yaml');
            }

            if (true === ($configuration['commands']['phinx']['enable'] ?? false)) {
                $loader->load('services/bridges/phinx.yaml');
            }
        }

        $this->configure($config, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $config, ContainerBuilder $container)
    {
        // Remove unused configurations
        $extension_config = array_intersect_key(
            $config, array_flip($this->pommx_config_names)
        );

        $this->extension_config = $extension_config;
    }

    public function getConfs(): array
    {
        return $this->extension_config;
    }

    public function setConf(string $name, array $extension_config): void
    {
        $this->extension_config[$name] = $extension_config;
    }
}
