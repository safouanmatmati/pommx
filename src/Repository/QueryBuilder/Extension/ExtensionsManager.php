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

namespace Pommx\Repository\QueryBuilder\Extension;

use Pommx\Repository\QueryBuilder\Extension\ExtensionInterface;
use Pommx\Tools\Exception\ExceptionManagerInterface;

final class ExtensionsManager
{
    /**
     *
     * @var ExtensionInterface[]
     */
    private $extensions = [];

    /**
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    /**
     *
     * @param ExceptionManagerInterface $exception_manager
     */
    public function __construct(ExceptionManagerInterface $exception_manager)
    {
        $this->exception_manager = $exception_manager;
    }

    /**
     * [addExtension description]
     *
     * @param  ExtensionInterface $extension
     * @return self
     */
    public function addExtension(ExtensionInterface $extension): self
    {
        if (true == isset($this->extensions[$extension->getIdentifier()])) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Invalid extension identifier.'.PHP_EOL
                    .'"%s" extension can\'t be loaded.'.PHP_EOL
                    .'"%" identifer is already associated to "%" extension'.PHP_EOL,
                    get_class($extension),
                    $extension->getIdentifier(),
                    get_class($this->extensions[$extension->getIdentifier()])
                )
            );
        }

        $extension->setExceptionManager($this->exception_manager);

        $this->extensions[$extension->getIdentifier()]= $extension;

        return $this;
    }

    /**
     * Returns loaded extensions.
     *
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }


    /**
     * Returns an extension from its identifier.
     *
     * @param  string $identifier
     * @throws LogicException if extension isn't loaded
     * @return ExtensionInterface
     */
    public function getExtension(string $identifier): ExtensionInterface
    {
        if (false == $this->hasExtention($identifier)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Invalid extension identifier.'.PHP_EOL
                    .'No one extension with "%s" identifier was found.'.PHP_EOL
                    .'Available identifiers are {"%s"}.',
                    $identifier,
                    join('", "', $this->getExtensionsIdentifiers())
                )
            );
        }

        return $this->extensions[$identifier];
    }

    /**
     * Indicates if an extension is loaded.
     *
     * @param  string $identifier
     * @return bool
     */
    public function hasExtention(string $identifier): bool
    {
        return isset($this->extensions[$identifier]);
    }

    /**
     * Returns loaded extensions identifiers.
     *
     * @return array
     */
    public function getExtensionsIdentifiers(): array
    {
        return array_keys($this->extensions);
    }
}
