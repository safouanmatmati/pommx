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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Phinx\Console\Command\Create as PhinxCreate;
use Phinx\Util\Util;

use Pommx\Bridge\Phinx\Console\Command\Adapter;
use Pommx\Bridge\Phinx\Migration\AbstractMigration;

class Create extends PhinxCreate
{
    use Adapter;

    protected function postConfigure(): self
    {
        return $this->setName('migration:create');
        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section = $output->section();

        $this->preExecute($input, $section);

        $parent_class = $this->getPommxMigrationConf()->getValue('parent_class');

        if ($parent_class !== AbstractMigration::class) {
            if (false == $this->hasMethodImplemented('getSchemasNames', $parent_class)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '"%s" class should implements "%s" method.',
                        $parent_class,
                        'getSchemasNames'
                    )
                );
            }

            $this->useCustomTemplate(true);
        }

        parent::execute($input, $section);

        $path = $this->getMigrationPath($input, $output);

        $file_name = Util::mapClassNameToFileName($input->getArgument('name'));

        $file_path = realpath($path) . DIRECTORY_SEPARATOR . $file_name;

        $wildcards = [
            '{$schema}'         => $input->getArgument('schema'),
            '{$base_namespace}' => $parent_class
        ];

        $contents = strtr(file_get_contents($file_path), $wildcards);

        if (file_put_contents($file_path, $contents) === false) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        $section->overwrite(sprintf('<info>%s migration file created.</info>', $file_path));
    }
}
