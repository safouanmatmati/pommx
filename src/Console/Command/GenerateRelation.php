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

use PommProject\Foundation\ParameterHolder;
use PommProject\Cli\Command\RelationAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Pommx\Console\Command\PommxAwareCommandInterface;
use Pommx\Console\Command\Traits\PommxAwareCommandTrait;
use Pommx\Console\Command\Traits\Generator;

class GenerateRelation extends RelationAwareCommand implements PommxAwareCommandInterface
{
    use PommxAwareCommandTrait;
    use Generator;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setName('pommx:generate:relation-all')
            ->setDescription('Generate structure, repository and entity file for a given relation.');

        $this->adapte();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute();

        parent::execute($input, $output);

        $this->generateStructure($input, $output);
        $this->generateEntity($input, $output);
        $this->generateRepository($input, $output);
    }
}
