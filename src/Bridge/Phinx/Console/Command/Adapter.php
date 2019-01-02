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
    private $name_prefix = 'pommx:database';
     /**
     * [$pomm description]
     *
     * @var Pomm
     */
    private $pomm;

    /**
     * Pommx configurations
     * @var array
     */
    private $configs;

    /**
     * Current Pommx migration configuration
     * @var Configuration
     */
   private $migration_configuration;

    /**
     *  Current Pommx seed configuration
     * @var Configuration
     */
    private $seed_configuration;

    /**
     * [private description]
     * @var bool
     */
    private $use_custom_template = false;

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
        $help = str_replace('-e development', '<config-name> <schema>', $this->getHelp());

        // Prefix name references by "pommx:phinx:"
        $help = str_replace('phinx ', "{$this->name_prefix}:", $help);

        $help = <<<EOT
$help
--------------------------------------------------------------------------------------------
You can <comment>custom directories, namespaces & parent class</comment> of your migration & seed files.
Use Pommx package configuration file. See <comment>bin/console config:dump-reference pommx</comment> for more details.

EOT;

        $this->setHelp($help);

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

        $this->postConfigure();
    }

    /**
     * [postConfigure description]
     * @return self
     */
    protected function postConfigure(): self
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     * Overload setName to prefix name automaticly.
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        // Prefix name
        parent::setName(sprintf('%s:%s', $this->name_prefix, $name));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
     protected function execute(InputInterface $input, OutputInterface $output)
     {
        // Restore <environment> option, as it's needed internaly by Phinx.
        // Set its value as <config-name> argument value
        $this->addOption('environment', null, InputOption::VALUE_REQUIRED, '', $input->getArgument('config-name'));

        $this->preExecute($input, $output);

        parent::execute($input, $output);
    }

    /**
     * Set migration and seed configuration depending on choosen config.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return self
     */
    protected function preExecute(InputInterface $input, OutputInterface $output): self
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

        $this
            ->setPommxMigrationConf(new Configuration($this->configs, $config_name, 'migrations'))
            ->setPommxSeedConf(new Configuration($this->configs, $config_name, 'seeds'));

        return $this;
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
        $migrations_config = $this->getPommxMigrationConf();
        $seeds_config      = $this->getPommxSeedConf();

        $phinx_conf['paths'] = [
            'migrations' => [
                $migrations_config->getNamespace($input->getArgument('schema')) =>
                    $migrations_config->getDirPath($input->getArgument('schema'))
            ],
            'seeds' => [
                $seeds_config->getNamespace($input->getArgument('schema')) =>
                    $seeds_config->getDirPath($input->getArgument('schema'))
            ]
        ];

        // Database connection
        //
        // Phinx doesn't use Pomm internaly to make database connection.
        // So, to avoid repeting connection configuration,
        // we retrieve it from selected Pommx configuration (eq. "config_name" argument).
        $connection = $this->pomm->getSession($config_name)->getConnection();
        $reflection = new \ReflectionClass($connection);
        $property   = $reflection->getProperty('configurator');
        $property->setAccessible(true);
        $configurator = $property->getValue($connection);

        $reflection = new \ReflectionClass($configurator);
        $property   = $reflection->getProperty('configuration');
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
                'host'    => $configuration->getParameter('host'),
                'name'    => $configuration->getParameter('database'),
                'user'    => $configuration->getParameter('user'),
                'pass'    => $configuration->getParameter('pass'),
                'port'    => $configuration->getParameter('port')
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

    /**
     * Returns the migration template filename.
     *
     * @return string
     */
    protected function getMigrationTemplateFilename()
    {
        $template = $this->use_custom_template
            ? 'Migration.custom.template.php.dist'
            : 'Migration.template.php.dist';

        return __DIR__
            .DIRECTORY_SEPARATOR
            .join(DIRECTORY_SEPARATOR, ['..', '..', 'Migration', $template]);
    }

    /**
     * Returns the seed template filename.
     *
     * @return string
     */
    protected function getSeedTemplateFilename()
    {
        $template = $this->use_custom_template
            ? 'Seed.custom.template.php.dist'
            : 'Seed.template.php.dist';

        return __DIR__
            .DIRECTORY_SEPARATOR
            .join(DIRECTORY_SEPARATOR, ['..', '..', 'Seed', $template]);
    }

    /**
     * [useCustomTemplate description]
     * @param bool $use_custom_template
     */
    protected function useCustomTemplate(bool $use_custom_template): void
    {
        $this->use_custom_template = $use_custom_template;
    }

    /**
     * [getPommxMigrationConf description]
     * @param  Configuration $configuration
     * @return self
     */
    protected function setPommxMigrationConf(Configuration $configuration): self
    {
        $this->migration_configuration = $configuration;
        return $this;
    }

    /**
     * [setPommxMigrationConf description]
     * @return Configuration
     */
    protected function getPommxMigrationConf(): Configuration
    {
        return $this->migration_configuration;
    }

    /**
     * [setPommxSeedConf description]
     * @param  Configuration $configuration
     * @return self
     */
    protected function setPommxSeedConf(Configuration $configuration): self
    {
        $this->seed_configuration = $configuration;
        return $this;
    }

    /**
     * [getPommxSeedConf description]
     * @return Configuration
     */
    protected function getPommxSeedConf(): Configuration
    {
        return $this->seed_configuration;
    }

    /**
     * [hasMethodImplemented description]
     * @param  string $method
     * @param  string $class
     * @return bool
     */
    protected function hasMethodImplemented(string $method, string $class): bool
    {
        $reflection = new \ReflectionClass($class);

        try {
            $method = $reflection->getMethod($method);
            return !$method->isAbstract();
        } catch (\Exception $e) {
            return false;
        }
    }
}
