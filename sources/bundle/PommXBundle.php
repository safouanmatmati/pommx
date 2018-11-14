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

namespace PommX\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

use PommX\Bundle\DependencyInjection\PommXExtension;
use PommX\Bundle\DependencyInjection\Compiler\CommandGeneratorPass;
use PommX\Bundle\DependencyInjection\Compiler\RepositoryClassFinderPass;
use PommX\Bundle\DependencyInjection\Compiler\QueryBuilderExtensionPass;

class PommXBundle extends Bundle
{
    /**
     * build
     *
     * @see Bundle
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CommandGeneratorPass());
        $container->addCompilerPass(new RepositoryClassFinderPass());
        $container->addCompilerPass(new QueryBuilderExtensionPass());
    }

    /**
     * getContainerExtension
     *
     * @see Bundle
     */
    public function getContainerExtension()
    {
        return new PommXExtension();
    }
}
