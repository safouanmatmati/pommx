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

namespace PommX\Console\Command\Traits;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\ParameterHolder;

use PommX\Generator\StructureGenerator;
use PommX\Console\Command\Configuration;

trait Definition
{
    /**
     * [private description]
     *
     * @var array
     */
    private $default_options = [];

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
    public function setConfiguration(array $options)
    {
        $this->default_options = $options;
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
        return new Configuration($this->default_options, $config_name, $type);
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
        $options   = [];
        $arguments = [];

        foreach ($this->getDefinition()->getOptions() as $option) {
            if (false == in_array($option->getName(), $this->getOptionsToDisable())) {
                $options[$option->getName()] = $option;
            }
        }

        foreach ($this->getDefinition()->getArguments() as $argument) {
            if ('schema' !== $argument->getName()) {
                $arguments[$argument->getName()] = $argument;
            }
        }

        $this->setDefinition($options + $arguments);

        $this
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force overwriting an existing file.'
            );

        // Changes 'schema' argument as required.
        $this->addArgument(
            'schema',
            InputArgument::REQUIRED,
            'Schema of the relation.'
        );

        $this->setHelp('Use "commands_generator" from "pomm_x" extension to defines options like file path, namespace, parent class etc...');
    }
}
