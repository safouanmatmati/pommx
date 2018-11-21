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

namespace Pommx\Bridge\Phinx\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Phinx\Config\Config;

use PommProject\Foundation\Pomm;

use Pommx\Bridge\Phinx\Console\Command\Manager;
use Pommx\Bridge\Phinx\Console\Command\Configuration;

trait Adapter
{
     /**
     * [$pomm description]
     *
     * @var Pomm
     */
    private $pomm;

    /**
     * [private description]
     * @var array
     */
    private $configs;

    public function setPommxConfigs(Pomm $pomm, array $configs)
    {
        $this->pomm    = $pomm;
        $this->configs = $configs;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            'config-name',
            InputArgument::REQUIRED,
            'Database configuration name to retrieve connection details and migrations/seeds configuration.'
        );

        // Add 'schema' argument but as required.
        $this->addArgument(
            'schema',
            InputArgument::REQUIRED,
            'Schema of the relation.'
        );

        parent::configure();

        // Changes help references
        //
        // Remove "phinx" references
        $help = str_replace('phinx', '', $this->getHelp());

        // Replace "-e development" by <config-name> references
        $help = str_replace('-e development', '<config-name>', $this->getHelp());

        // Prefix name references by "pommx:phinx:"
        $help = str_replace($this->getName(), "pommx:phinx:{$this->getName()}", $help);

        $this->setHelp($help);

        // Prefix name by "pommx:phinx:"
        $this->setName("pommx:phinx:".$this->getName());

        $definition = [];

        // Keep arguments definitions
        foreach ($this->getDefinition()->getArguments() as $argument) {
            $definition[$argument->getName()] = $argument;
        }

        // Changes options definitions.
        // <environment>, <configuration> & <parser> options are not used anymore, remove them
        //
        // NOTE: <configuration> & <parser> are replaced internaly by $this->configs[<config-name>]
        // NOTE: <environment> is replaced by <config-name> argument
        //
        foreach ($this->getDefinition()->getOptions() as $option) {
            if (false == in_array($option->getName(), ['configuration', 'environment', 'parser'])) {
                $definition[$option->getName()] = $option;
            }
        }

        $this->setDefinition($definition);
    }

    /**
     * {@inheritdoc}
     */
     protected function execute(InputInterface $input, OutputInterface $output)
     {
        // Restore <environment> option, as it needed internal by Phinx.
        // Set its value as <config-name> argument value
        $this->addOption('environment', null, InputOption::VALUE_REQUIRED, '', $input->getArgument('config-name'));

        parent::execute($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadConfig(InputInterface $input, OutputInterface $output)
    {
        $config_name = $input->getArgument('config-name');

        if (false == array_key_exists($config_name, $this->configs)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" Pommx config name doesn\'t exists.'.PHP_EOL
                    .'Existing configs are {"%s"}',
                    $config_name,
                    join('", "', array_keys($this->configs))
                )
            );
        }

        $data = $this->configs[$config_name];

        $phinx_conf = [
            'version_order'   => $data['version_order'],
            'migration_table' => $data['migration_table']
        ];

        // Paths
        $migrations_config = new Configuration($this->configs, $config_name, 'migrations');
        $seeds_config      = new Configuration($this->configs, $config_name, 'seeds');

        $phinx_conf['paths'] = [
            'migrations' => [
                $migrations_config->getNamespace($input->getArgument('schema')) =>
                    $migrations_config->getPathFile($input->getArgument('schema'))
            ],
            'seeds' => [
                $seeds_config->getNamespace($input->getArgument('schema')) =>
                    $seeds_config->getPathFile($input->getArgument('schema'))
            ]
        ];

        // Database connection
        //
        // Phinx doesn't use Pomm internaly to make database connection.
        // So, to avoid repeting connection configuration,
        // we retrieve it from selected Pommx configuration (eq. "config_name" argument).
        $connection = $this->pomm->getSession($config_name)->getConnection();
        $reflection = new \ReflectionClass($connection);
        $property = $reflection->getProperty('configurator');
        $property->setAccessible(true);
        $configurator = $property->getValue($connection);

        $reflection = new \ReflectionClass($configurator);
        $property = $reflection->getProperty('configuration');
        $property->setAccessible(true);

        $configuration = $property->getValue($configurator);

        $configuration
            ->mustHave('adapter')
            ->mustHave('host')
            ->mustHave('database')
            ->mustHave('user')
            ->mustHave('pass')
            ->mustHave('port');

        $phinx_conf['environments'] = [
            'default_migration_table' => $data['migration_table'],
            'default_database'        => $config_name,
            $config_name => [
                'adapter' => $configuration->getParameter('adapter'),
                'host' => $configuration->getParameter('host'),
                'name' => $configuration->getParameter('database'),
                'user' => $configuration->getParameter('user'),
                'pass' => $configuration->getParameter('pass'),
                'port' => $configuration->getParameter('port')
            ]
        ];

        parent::setConfig(new Config($phinx_conf));
    }

    /**
     * {@inheritdoc}
     *
     * Use a manager adapted to Pomm.
     */
    protected function loadManager(InputInterface $input, OutputInterface $output)
    {
        if (true == is_null($manager = $this->getManager()) || false == is_a($manager, Manager::class)) {
            $manager = new Manager($this->pomm, $this->getConfig(), $input, $output);
            $this->setManager($manager);
        }
    }
}
