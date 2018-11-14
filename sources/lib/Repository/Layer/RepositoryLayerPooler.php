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

namespace PommX\Repository\Layer;

use PommProject\ModelManager\ModelLayer\ModelLayerPooler;

use PommX\Repository\AbstractRepository;
use PommX\Repository\Layer\DefaultLayer;
use PommX\Tools\Exception\ExceptionManagerInterface;

class RepositoryLayerPooler extends ModelLayerPooler
{
    /**
     * ExceptionManagerInterface
     *
     * @var [type]
     */
    private $exception_manager;

    /**
     * [__construct description]
     *
     * @param ExceptionManagerInterface $exception_manager [description]
     */
    public function __construct(
        ExceptionManagerInterface $exception_manager
    ) {
        $this->exception_manager = $exception_manager;
    }

    /**
     * getPoolerType
     *
     * @see ClientPoolerInterface
     */
    public function getPoolerType()
    {
        return 'repository_layer';
    }

    /**
     * createClient
     *
     * {@inheritdoc}
     */
    protected function createClient($identifier)
    {
        $reflection = new \ReflectionClass($identifier);

        if (true == $reflection->isSubclassOf(AbstractRepository::class)) {
            $layer = new DefaultLayer($identifier);
        } else {
             $layer = parent::createClient($identifier);
        }

        $layer->setExceptionManager($this->exception_manager);

        return $layer;
    }
}
