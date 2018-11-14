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

namespace PommX\Bridge\ApiPlatform\DataPersister\SubDataPersister;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\Where;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;

use PommX\Repository\QueryBuilder;
use PommX\Repository\AbstractRepository;
use PommX\Entity\AbstractEntity;
use PommX\EntityManager\EntityManager;

abstract class AbstractDataPersister implements DataPersisterInterface
{
    /**
     * @var Pomm
     */
    protected $pomm;

    /**
     * @var EntityManager
     */
    protected $entity_manager;

    /**
     * @param Pomm $pomm
     */
    public function __construct(Pomm $pomm, EntityManager $entity_manager, AbstractRepository $repository)
    {
        $this->pomm = $pomm;

        $this->repository = $repository;

        $this->entity_manager = $entity_manager;
    }

    public function supports($data): bool
    {
        return get_class($data) === $this->repository->getEntityClass();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        $this->entity_manager->flush($data);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data)
    {
        $data->setStatus([$data::STATUS_TO_DELETE => true]);
        $this->entity_manager->flush($data);
    }
}
