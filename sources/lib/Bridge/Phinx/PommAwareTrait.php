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

namespace PommX\Bridge\Phinx\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Phinx\Config\Config;

use PommProject\Foundation\Pomm;

use PommX\Bridge\Phinx\Console\Command\Manager;

trait PommAwareTrait
{
    /**
     * [$pomm description]
     *
     * @var Pomm
     */
    private $pomm;

    private $external_conf;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pomm $pomm, array $external_conf = null, string $name = null)
    {
        $this->pomm = $pomm;
        $this->external_conf = $external_conf;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadConfig(InputInterface $input, OutputInterface $output)
    {
        if (false == is_null($this->external_conf)) {
            parent::setConfig(new Config($this->external_conf));

            $output->writeln('<info>using config from command arguments.</info>');
        }
    }

    /**
     * Remove 'e' shortcut from phinx to avoid Symfony framework conflict
     * Adapte $command reference
     *
     * {@inheritdoc}
     */
    protected function adapte($command)
    {
        parent::configure();

        // Set 'environment' option as deprecated
        if (true ==  $this->getDefinition()->hasOption('environment')) {
            $phinx_e_option = $this->getDefinition()->getOptionForShortcut('e');
            $phinx_e_option = new InputOption(
                $phinx_e_option->getName(),
                null,
                InputOption::VALUE_REQUIRED,
                $phinx_e_option->getDescription()
            );

            $options = $this->getDefinition()->getOptions();
            unset($options[$phinx_e_option->getName()]);
            $options[$phinx_e_option->getName()] = $phinx_e_option;
            $this->setDefinition($options);

            // Changes help "e" references
            $help = str_replace('-e ', '--environment=', $this->getHelp());
            $this->setHelp($help);
        }

        // Changes help references
        $help = str_replace('phinx', '', $this->getHelp());
        $help = str_replace($command, "pommx:phinx:$command", $help);
        $this->setHelp($help);

        // Changes name  references
        $this->setName("pommx:phinx:".$this->getName());
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
