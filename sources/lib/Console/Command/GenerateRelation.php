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

namespace PommX\Console\Command;

use PommProject\Foundation\ParameterHolder;
use PommProject\Cli\Command\RelationAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PommX\Console\Command\Traits\InspectorPoolerAwareCommand;
use PommX\Console\Command\Traits\Definition;

use PommX\Generator\EntityGenerator;
use PommX\Generator\StructureGenerator;
use PommX\Generator\RepositoryGenerator;

class GenerateRelation extends RelationAwareCommand
{
    use InspectorPoolerAwareCommand;
    use Definition;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setName('pommx:generate:relation-all')
            ->setDescription('Generate structure, repository and entity file for a given relation.');

        $this->overrideDefinition();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->addRequiredParentOptions();

        parent::execute($input, $output);

        $this->mustBeModelManagerSession($this->getSession());

        $structure_name = $this->generateStructure($input, $output);
        $entity_name    = $this->generateEntity($input, $output);
        $this->generateRepository($input, $output, $entity_name, $structure_name);
    }

    /**
     * Generates structure file for a relation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string
     */
    private function generateStructure(InputInterface $input, OutputInterface $output): string
    {
        $configuration = $this->getConfiguration(
            $input->getArgument('config-name'),
            'structure'
        );

        $infos = $configuration->getFileInfos($this->schema, $this->relation);

        $path_file = $infos['path_file'];

        if (!file_exists($path_file) || $input->getOption('force')) {
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
        } elseif ($output->isVerbose()) {
            $this->writelnSkipFile($output, $path_file, 'structure');
        }

        return $infos['name'];
    }

    /**
     * Generates entity file for a relation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string
     */
    private function generateEntity(InputInterface $input, OutputInterface $output): string
    {
        $configuration = $this->getConfiguration(
            $input->getArgument('config-name'),
            'entity'
        );

        $infos = $configuration->getFileInfos($this->schema, $this->relation);

        $path_file = $infos['path_file'];

        if (!file_exists($path_file) || $input->getOption('force')) {
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
        } elseif ($output->isVerbose()) {
            $this->writelnSkipFile($output, $path_file, 'entity');
        }

        return $infos['name'];
    }

    /**
     * Generates repository file for a relation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  string          $entity_class
     * @param  string          $structure_class
     * @return string
     */
    private function generateRepository(
        InputInterface $input,
        OutputInterface $output,
        string $entity_class,
        string $structure_class
    ): string {
        $configuration = $this->getConfiguration(
            $input->getArgument('config-name'),
            'repository'
        );

        $infos = $configuration->getFileInfos($this->schema, $this->relation);

        $path_file = $infos['path_file'];

        if (!file_exists($path_file) || $input->getOption('force')) {
            $this->updateOutput(
                $output,
                (new RepositoryGenerator(
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
                                'entity'          => $infos['short_name'],
                                'parent_class'    => $configuration->getValue('parent_class'),
                                'entity_class'    => $entity_class,
                                'structure_class' => $structure_class
                            ]
                        )
                    )
                )
            );
        } elseif ($output->isVerbose()) {
            $this->writelnSkipFile($output, $path_file, 'repository');
        }

        return $infos['name'];
    }
}
