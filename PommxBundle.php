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

use Pommx\DependencyInjection\PommxExtension;
use Pommx\DependencyInjection\Compiler\PassInterface;

class PommxBundle extends Bundle
{
    /**
     * [protected description]
     * @var PommxExtension
     */
    protected $extension;

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $loader = $this->getContainerExtension()->getLoader($container, __DIR__.'/src/');

        $loader->load('Resources/config/dependency_injection/*.{yaml,yml}', 'glob');
        $loader->load('Resources/config/services/*.{yaml,yml}', 'glob');
        $loader->load('Bridge/*/Resources/config/dependency_injection.{yaml,yml}', 'glob');

        // Add dependency injection compiler pass
        foreach ($container->findTaggedServiceIds('pommx.di.pass') as $id => $tags) {
            $definition = $container->getDefinition($id);

            if (true == is_subclass_of($definition->getClass(), PassInterface::class)) {
               $pass = $container->get($id);
               $pass->setPommxExtension($this->getContainerExtension());
               $container->addCompilerPass($pass);
           } else {
               throw new \LogicException(
                   sprintf(
                       '"%s", as dependency injection compiler pass, has to implement "%s" interface.',
                       $id,
                       PassInterface::class
                   )
               );
           }
        }
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
