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

    public function __construct(Pomm $pomm)
    {
        $this->pomm = $pomm;

        parent::__construct();
    }

    /**
     * Replaces 'e' shortcut and reference by 'env', to avoid Symfony framework conflict
     * Adapte $command reference
     *
     * {@inheritdoc}
     */
    protected function adapte($command)
    {
        parent::configure();

        // Changes original phinx "e" shortcut, that is in conflict with "e" symfony one
        if (true ==  $this->getDefinition()->hasOption('environment')) {
            $e_option = $this->getDefinition()->getOptionForShortcut('e');
            $options = $this->getDefinition()->getOptions();

            $e_option = new InputOption(
                $e_option->getName(),
                'env',
                InputOption::VALUE_REQUIRED,
                $e_option->getDescription()
            );
            $options[$e_option->getName()] = $e_option;
            $this->setDefinition($options);

            // Changes help "e" references
            $help = str_replace('-e ', '-env ', $this->getHelp());
            $this->setHelp($help);
        }

        // Changes help "$command" references
        $help = str_replace('phinx', '', $this->getHelp());
        $help = str_replace($command, "phinx:$command", $help);
        $this->setHelp($help);
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
