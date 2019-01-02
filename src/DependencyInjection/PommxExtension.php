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
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Pommx\DependencyInjection\Configuration;
use Pommx\DependencyInjection\BridgeConfigurationInterface;
use Pommx\DependencyInjection\Compiler\PassInterface;
use Pommx\Session\SessionBuilder;

class PommxExtension extends Extension implements PrependExtensionInterface
{
    /**
     * [$pommx_sessions description]
     *
     * @var array
     */
    private $pommx_sessions;

    /**
     * [$sessions description]
     *
     * @var array
     */
    private $sessions_configurations;

    /**
     * [private description]
     * @var array
     */
    private $pomm_configuration;

    /**
     * [private description]
     * @var Configuration
     */
    private $configuration;

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
                $configurations = $extension['configuration'] ?? [];

                foreach ($configurations as $name => $config) {
                    $session_builder = $config['session_builder'] ?? null;

                    // A Pommx builer session is already defined
                    if (false == is_null($session_builder)
                        && (true == is_subclass_of($session_builder, SessionBuilder::class)
                        || $session_builder == SessionBuilder::class)
                    ) {
                        // Do not create another one.
                        $define_pommx_session = false;

                        // Register it as a Pommx builder session
                        $this->pommx_sessions[$name] = [];
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

                $this->pommx_sessions['pommx.db'] = [];

                // Add it to Pomm extension
                $container->prependExtensionConfig('pomm', ['configuration' => $configurations]);
            }
        } else {
            throw new \LogicException(
                'Failed to initialize Pommx bundle.'
                .'It require Pomm bundle to be registered first.'
            );
        }

        // Add Pommx sessions to extension config
        $container->prependExtensionConfig('pommx', ['sessions' => $this->pommx_sessions]);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        // Load bridges services only if they are enabled
        $loader = $this->getLoader($container, __DIR__.'/../');

        foreach ($config['bridges'] as $name => $bridge) {
            if (true === ($bridge['enable'] ?? false)) {
                $loader->load(
                    sprintf('Bridge/*/Resources/config/%s.{yaml,yml}', $name),
                    'glob'
                );
            }
        }

        $this->configure($config, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $config, ContainerBuilder $container)
    {
        // Remove useless configurations
        $sessions_configurations = array_intersect_key(
            $config['sessions'] ?? [], $this->pommx_sessions
        );

        $this->sessions_configurations = $sessions_configurations;
        $this->pomm                    = $config['pomm'];
        $this->bridges                 = $config['bridges'];
    }

    public function getSessions(): array
    {
        return $this->sessions_configurations;
    }

    public function setSessions(string $name, array $session_configuration): void
    {
        $this->sessions_configurations[$name] = $session_configuration;
    }

    public function getPommConfiguration(): array
    {
        return $this->pomm;
    }

    public function getBridgeConfiguration(string $bridge_name): array
    {
        return $this->bridges[$bridge_name];
    }

    public function getLoader(ContainerBuilder $container, string $path): LoaderInterface
    {
        $file_locator    = new FileLocator($path);
        $global_loader   = new GlobFileLoader($container, $file_locator);
        $yaml_loader     = new YamlFileLoader($container, $file_locator);
        $loader_resolver = new LoaderResolver([$global_loader, $yaml_loader]);

        return new DelegatingLoader($loader_resolver);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        if (false == is_null($this->configuration)) {
            return $this->configuration;
        }

        $loader = $this->getLoader($container, __DIR__.'/../');

        $loader->load('Resources/config/dependency_injection/*.{yaml,yml}', 'glob');
        $loader->load('Resources/config/services/*.{yaml,yml}', 'glob');
        $loader->load('Bridge/*/Resources/config/dependency_injection.{yaml,yml}', 'glob');

        // Add bridges configurations definitions to the main configuration definition
        $bridges = [];

        foreach ($container->findTaggedServiceIds('pommx.di.bridge.configuration') as $id => $tags) {
            $definition = $container->getDefinition($id);

            if (is_subclass_of($definition->getClass(), BridgeConfigurationInterface::class)) {
               $bridges[$id] = $container->get($id);
           } else {
               throw new \LogicException(
                   sprintf(
                       '"%s", as a dependency injection bridge configuration, has to implement "%s" interface.',
                       $id,
                       BridgeConfigurationInterface::class
                   )
               );
           }
        }

        // $dumper = new YamlReferenceDumper();
        // var_dump($dumper->dump($conf));die(__METHOD__);

        // Main configuration definition
        $configuration = new Configuration($bridges);
        // var_dump($configuration->getConfigTreeBuilder()->buildTree()->getDefaultValue());
        return $this->configuration = $configuration;
    }
}
