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

namespace Pommx\Console\Command\Traits;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\ParameterHolder;
use PommProject\Foundation\Session\Session;

use Pommx\Console\Command\Configuration;
use Pommx\Console\Command\PommxAwareCommandInterface;

use Pommx\Inspector\InspectorPooler;

trait PommxAwareCommandTrait
{
    /**
     * [private description]
     *
     * @var array
     */
    private $configurations = [];

    /**
     * [private description]
     *
     * @var Session
     */
    private $session;

    /**
     * [private description]
     *
     * @var string
     */
    private $replaced_command_class;

    public function __construct(Pomm $pomm)
    {
        parent::__construct();

        $this->setPomm($pomm);
    }

    /**
     * getSession
     *
     * Return a session.
     *
     * @access protected
     * @return Session
     */
    public function getSession()
    {
        if ($this->session === null) {
            $this->session = $this
                ->getPomm()
                ->getSession($this->config_name)
                ->registerClientPooler(new InspectorPooler());
        }

        return $this->session;
    }

    /**
     * Define Pomm command console that this class is supposed to replace.
     *
     * @param string $class [description]
     */
    public function setReplacedCommandClass(string $class): PommxAwareCommandInterface
    {
        $this->replaced_command_class = $class;
        return $this;
    }

    /**
     * Return Pomm command console class name supposed to be replaced by this class;
     *
     * @return null|string
     */
    public function getReplacedCommandClass(): ?string
    {
        return $this->replaced_command_class;
    }

    /**
     * Returns useless options list.
     * This options are not used anymore, but still required by parents class processes.
     *
     * @return array
     */
    public function getOptionsToDisable(): array
    {
        return [
            'prefix-dir',
            'prefix-ns',
            'bootstrap-file',
            'flexible-container',
            'psr4'
        ];
    }

    /**
     * Add required (but unused anymore) options.
     */
    public function restoreDisabledOptions(): PommxAwareCommandInterface
    {
        foreach ($this->getOptionsToDisable() as $option) {
            $this->addOption($option);
        }

        return $this;
    }
    /**
     * [preExecute description]
     *
     * @return PommxAwareCommandInterface [description]
     */
    public function preExecute(): PommxAwareCommandInterface
    {
        $this->restoreDisabledOptions();

        return $this;
    }

    /**
     * Removes unused options.
     * Change 'schema' argument as required.
     *
     * @return PommxAwareCommandInterface [description]
     */
    public function adapte(): PommxAwareCommandInterface
    {
        $options = $arguments = [];

        // Retrieves currents options except thoses not used anymore
        foreach ($this->getDefinition()->getOptions() as $option) {
            if (false == in_array($option->getName(), $this->getOptionsToDisable())) {
                $options[$option->getName()] = $option;
            }
        }

        // Retrieves currents arguments, except <schema>
        foreach ($this->getDefinition()->getArguments() as $argument) {
            if ('schema' !== $argument->getName()) {
                $arguments[$argument->getName()] = $argument;
            }
        }

        // Clear definition
        $this->setDefinition([]);

        // Add 'schema' argument as required now
        $this->addArgument(
            'schema',
            InputArgument::REQUIRED,
            'Schema of the relation.'
        );

        $arguments_bis = [
            'config-name' => $arguments['config-name'],
            'schema'      => $this->getDefinition()->getArgument('schema')
        ];

        $this->setDefinition(array_merge($arguments_bis, $arguments, $options));

        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Force overwriting an existing file.'
        );

        $help = <<<EOT
You can <comment>custom directories, namespaces & parent class</comment> of your entity, structure & repository files.
Use Pommx package configuration file. See <comment>bin/console config:dump-reference pommx</comment> for more details.
EOT;
        $this->setHelp($help);

        return $this;
    }

    /**
     * Returns configuration depending on $config_name & $type.
     *
     * @param  string $config_name [description]
     * @param  string $type        [description]
     * @return Configuration              [description]
     */
    public function getConfiguration(string $config_name, string $type): Configuration
    {
        return new Configuration($this->configurations, $config_name, $type);
    }

    /**
     * Set default configurations.
     *
     * @param  array $configurations [description]
     * @return PommxAwareCommandInterface                 [description]
     */
    public function setConfigurations(array $configurations): PommxAwareCommandInterface
    {
        $this->configurations = $configurations;

        return $this;
    }
}
