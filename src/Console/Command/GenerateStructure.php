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

namespace Pommx\Console\Command;

use PommProject\Cli\Command\RelationAwareCommand;
use PommProject\Foundation\ParameterHolder;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Pommx\Console\Command\Traits\InspectorPoolerAwareCommand;
use Pommx\Console\Command\Traits\Definition;

use Pommx\Generator\StructureGenerator;

class GenerateStructure extends RelationAwareCommand
{
    use InspectorPoolerAwareCommand;
    use Definition;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('pommx:generate:structure')
            ->setDescription('Generate a RowStructure file based on table schema.');

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

        $configuration = $this->getConfiguration(
            $input->getArgument('config_name'),
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
    }
}
