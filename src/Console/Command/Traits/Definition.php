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

use Pommx\Generator\StructureGenerator;
use Pommx\Console\Command\Configuration;

trait Definition
{
    /**
     * [private description]
     *
     * @var array
     */
    private $configurations = [];

    public function __construct(Pomm $pomm)
    {
        parent::__construct();

        $this->setPomm($pomm);
    }

    /**
     * Set default configuration options.
     *
     * @param array $options [description]
     */
    public function setConfigurations(array $options)
    {
        $this->configurations = $options;
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
     * Returns useless options list.
     * This options are not used anymore, but still required by parents class processes.
     *
     * @return array
     */
    private function getOptionsToDisable(): array
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
    private function addRequiredParentOptions(): void
    {
        foreach ($this->getOptionsToDisable() as $option) {
            $this->addOption($option);
        }
    }

    /**
     * Removes unused options.
     * Change 'schema' argument as required.
     */
    private function overrideDefinition(): void
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

        $this->setDefinition([]);

        // Add 'schema' argument but as required.
        $this->addArgument(
            'schema',
            InputArgument::REQUIRED,
            'Schema of the relation.'
        );

        $arguments_bis = [
            'config-name' => $arguments['config-name'],
            'schema' => $this->getDefinition()->getArgument('schema')
        ];

        $this->setDefinition($arguments_bis + $arguments + $options);

        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Force overwriting an existing file.'
        );

        $this->setHelp('Use "pommx.<config-name>.commands" extension option to defines resources (entities, repository, structure) path, namespace, parent class etc...');
    }
}
