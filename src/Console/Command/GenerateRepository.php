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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PommProject\Cli\Command\RelationAwareCommand;

use Pommx\Console\Command\PommxAwareCommandInterface;
use Pommx\Console\Command\Traits\PommxAwareCommandTrait;
use Pommx\Console\Command\Traits\Generator;

class GenerateRepository extends RelationAwareCommand implements PommxAwareCommandInterface
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
            ->setName('pommx:generate:repository')
            ->setDescription('Generate a new repository class.');

        $this->adapte();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute();

        parent::execute($input, $output);

        $this->generateRepository($input, $output);
    }
}
