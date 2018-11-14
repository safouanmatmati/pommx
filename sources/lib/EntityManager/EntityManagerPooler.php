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

namespace PommX\EntityManager;

use PommProject\Foundation\Client\ClientPoolerInterface;
use PommProject\Foundation\Client\ClientPooler;
use PommX\EntityManager\EntityManager;

class EntityManagerPooler extends ClientPooler
{
    /**
     * EntityManager
     *
     * @var EntityManager
     */
    private $entity_manager;

    /**
     * [__construct description]
     * @param EntityManager $repo_class_finder [description]
     */
    public function __construct(EntityManager $entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    /**
     * getPoolerType
     *
     * @see ClientPoolerInterface
     */
    public function getPoolerType()
    {
        return 'entity_manager';
    }

    /**
     * createClient
     *
     * @see    ClientPooler
     * @return EntityManager
     */
    protected function createClient($identifier)
    {
        return $this->entity_manager;
    }
}
