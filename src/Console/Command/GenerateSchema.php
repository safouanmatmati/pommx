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

use Pommx\Console\Command\PommxAwareCommandInterface;
use Pommx\Console\Command\Traits\PommxAwareCommandTrait;

class GenerateSchema extends SchemaAwareCommand implements PommxAwareCommandInterface
{
    use PommxAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setName('pommx:generate:schema-all')
            ->setDescription('Generate structure, repository and entity file for all relations in a schema.');

        $this->adapte();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute();

        parent::execute($input, $output);

        $relations = $this->getSession()
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
                    'config-name' => $this->config_name,
                    'relation'    => $relation_info['name'],
                    'schema'      => $this->schema,
                    '--force'     => $input->getOption('force')
                ];
                $command->run(new ArrayInput($arguments), $output);
            }
        }
    }
}
