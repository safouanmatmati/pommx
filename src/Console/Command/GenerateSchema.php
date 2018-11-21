<?php

/*
 * This file is part of the Weasyo package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pommx\Console\Command;

use PommProject\Cli\Command\SchemaAwareCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Pommx\Console\Command\Traits\InspectorPoolerAwareCommand;
use Pommx\Console\Command\Traits\Definition;

class GenerateSchema extends SchemaAwareCommand
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
            ->setName('pommx:generate:schema-all')
            ->setDescription('Generate structure, repository and entity file for all relations in a schema.');

        $this->overrideDefinition();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->addRequiredParentOptions();

        parent::execute($input, $output);

        $session = $this->mustBeModelManagerSession($this->getSession());

        $relations = $session
            ->getInspector()
            ->getSchemaRelations($this->fetchSchemaOid());

        $output->writeln(
            sprintf(
                "Scanning schema '<fg=green>%s</fg=green>'.",
                $this->schema
            )
        );

        if ($relations->isEmpty()) {
            $output->writeln("<bg=yellow>No relations found.</bg=yellow>");
        } else {
            foreach ($relations as $relation_info) {
                $command = $this->getApplication()->find('pommx:generate:relation-all');
                $arguments = [
                    'command'     => 'pommx:generate:relation-all',
                    'config_name' => $this->config_name,
                    'relation'    => $relation_info['name'],
                    'schema'      => $this->schema,
                    '--force'     => $input->getOption('force')
                ];
                $command->run(new ArrayInput($arguments), $output);
            }
        }
    }
}
