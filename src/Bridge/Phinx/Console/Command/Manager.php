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
use Symfony\Component\Console\Output\OutputInterface;

use PommProject\Foundation\Pomm;

use Pommx\Bridge\Phinx\PommAwareInterface;

use Phinx\Config\ConfigInterface;
use Phinx\Migration\Manager as PhinxManager;

class Manager extends PhinxManager
{
    /**
     * Pomm
     *
     * @var Pomm
     */
    private $pomm;

    /**
     * [__construct description]
     *
     * @param Pomm            $pomm   [description]
     * @param ConfigInterface $config [description]
     * @param InputInterface  $input  [description]
     * @param OutputInterface $output [description]
     */
    public function __construct(
        Pomm $pomm,
        ConfigInterface $config,
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->pomm = $pomm;
        parent::__construct($config, $input, $output);
    }

    /**
     * Set pomm instance to seeds implementing PommAwareInterface interface.
     *
     * {@inheritdoc}
     */
    public function getSeeds()
    {
        $seeds = parent::getSeeds();

        foreach ($seeds as $seed) {
            if ($seed instanceof PommAwareInterface) {
                $seed->setPomm($this->pomm);
            }
        }

        return $seeds;
    }

    /**
     * Display "file not found" message.
     *
     * {@inheritdoc}
     *
     * @return string[]
     */
    protected function getSeedFiles()
    {
        $config = $this->getConfig();
        $paths  = $config->getSeedPaths();
        $files  = parent::getSeedFiles();

        if (true == empty($files)) {
            $this->displayFileNotFound($paths, 'seed file');
        }

        return $files;
    }

    /**
     * Display "file not found" message.
     *
     * {@inheritdoc}
     *
     * @return string[]
     */
    protected function getMigrationFiles()
    {
        $config = $this->getConfig();
        $paths  = $config->getMigrationPaths();
        $files  = parent::getMigrationFiles();

        if (true == empty($files)) {
            $this->displayFileNotFound($paths, 'migration file');
        }

        return $files;
    }

    /**
     * Display message indicating that no file was found, if it's the case.
     *
     * @param string[]  $paths [description]
     * @param string $type  [description]
     */
    private function displayFileNotFound(array $paths, string $type): void
    {
        $this->getOutput()->writeln(
            sprintf(
                '<comment>No %s found in following folders </comment>:',
                $type
            )
        );

        foreach ($paths as $path) {
            $this->getOutput()->writeln(
                sprintf('- <comment>%s</comment>', $path)
            );
        }
    }

    /**
     * Set pomm instance to migrations implementing PommAwareInterface interface.
     *
     * {@inheritdoc}
     */
    public function getMigrations()
    {
        $migrations = parent::getMigrations();

        foreach ($migrations as $migration) {
            if ($migration instanceof PommAwareInterface) {
                $migration->setPomm($this->pomm);
            }
        }

        return $migrations;
    }
}
