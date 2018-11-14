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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PommX\Console\Command\Traits\InspectorPoolerAwareCommand;
use PommX\Console\Command\Traits\Definition;

use PommX\Generator\RepositoryGenerator;

class GenerateRepository extends RelationAwareCommand
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
            ->setName('pommx:generate:repository')
            ->setDescription('Generate a new repository file.');

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
    }
}
