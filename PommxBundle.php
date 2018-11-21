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

namespace Pommx\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

use Pommx\DependencyInjection\PommxExtension;
use Pommx\DependencyInjection\Compiler\CommandGeneratorPass;
use Pommx\DependencyInjection\Compiler\RepositoryClassFinderPass;
use Pommx\DependencyInjection\Compiler\QueryBuilderExtensionPass;
use Pommx\DependencyInjection\Compiler\PhinxPass;

class PommxBundle extends Bundle
{
    protected $extension;

    /**
     * build
     *
     * @see Bundle
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CommandGeneratorPass($this->getContainerExtension()));

        $container->addCompilerPass(new RepositoryClassFinderPass($this->getContainerExtension()));

        $container->addCompilerPass(new QueryBuilderExtensionPass($this->getContainerExtension()));

        $container->addCompilerPass(new PhinxPass($this->getContainerExtension()));
    }

    /**
     * getContainerExtension
     *
     * @see Bundle
     */
    public function getContainerExtension()
    {
        if (false == isset($this->extension)) {
            $this->extension = new PommxExtension();
        }

        return $this->extension;
    }
}
