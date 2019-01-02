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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\ParameterHolder;

use Pommx\Generator\EntityGenerator;
use Pommx\Generator\StructureGenerator;
use Pommx\Generator\RepositoryGenerator;

use Pommx\Console\Command\Configuration;

trait Generator
{
    /**
     * Generates entity file for a relation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string
     */
    public function generateEntity(InputInterface $input, OutputInterface $output): string
    {
        $configuration = $this->getConfiguration(
            $input->getArgument('config-name'),
            'entity'
        );

        $infos = $configuration->getFileInfos($this->schema, $this->relation);

        $this->updateOutput(
            $output,
            (new EntityGenerator(
                $this->getSession(),
                $this->schema,
                $this->relation,
                $infos['path_file'],
                $infos['namespace']
            ))->generate(
                new ParameterHolder(
                    array_merge(
                        $input->getArguments(),
                        [
                          'entity'       => $infos['short_name'],
                          'parent_class' => $configuration->getValue('parent_class')
                        ]
                    )
                )
            )
        );

        return $infos['name'];
    }

    /**
     * Generates structure file for a relation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string
     */
    public function generateStructure(InputInterface $input, OutputInterface $output): string
    {
        $configuration = $this->getConfiguration(
            $input->getArgument('config-name'),
            'structure'
        );

        $infos = $configuration->getFileInfos($this->schema, $this->relation);

        $this->updateOutput(
            $output,
            (new StructureGenerator(
                $this->getSession(),
                $this->schema,
                $this->relation,
                $infos['path_file'],
                $infos['namespace']
            ))->generate(
                new ParameterHolder(
                    array_merge(
                        $input->getArguments(),
                        [
                          'entity'       => $infos['short_name'],
                          'parent_class' => $configuration->getValue('parent_class')
                        ]
                    )
                )
            )
        );

        return $infos['name'];
    }

    /**
     * Generates repository file for a relation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string
     */
    public function generateRepository(
        InputInterface $input,
        OutputInterface $output
    ): string {
        $repo_conf = $this->getConfiguration(
            $input->getArgument('config-name'),
            'repository'
        );

        $entity_conf = $this->getConfiguration(
            $input->getArgument('config-name'),
            'entity'
        );

        $structure_conf = $this->getConfiguration(
            $input->getArgument('config-name'),
            'structure'
        );

        $repo_infos      = $repo_conf->getFileInfos($this->schema, $this->relation);
        $entity_infos    = $entity_conf->getFileInfos($this->schema, $this->relation);
        $structure_infos = $structure_conf->getFileInfos($this->schema, $this->relation);

        $this->updateOutput(
            $output,
            (new RepositoryGenerator(
                $this->getSession(),
                $this->schema,
                $this->relation,
                $repo_infos['path_file'],
                $repo_infos['namespace']
            ))->generate(
                new ParameterHolder(
                    array_merge(
                        $input->getArguments(),
                        [
                            'entity'          => $repo_infos['short_name'],
                            'parent_class'    => $repo_conf->getValue('parent_class'),
                            'entity_class'    => $entity_infos['name'],
                            'structure_class' => $structure_infos['name']
                        ]
                    )
                )
            )
        );

        return $repo_infos['name'];
    }
}
