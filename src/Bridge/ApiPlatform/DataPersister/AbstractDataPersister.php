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

namespace Pommx\Bridge\ApiPlatform\DataPersister;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\Where;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;

use Pommx\Entity\AbstractEntity;
use Pommx\EntityManager\EntityManager;

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
    public function __construct(Pomm $pomm, EntityManager $entity_manager)
    {
        $this->pomm = $pomm;

        $this->entity_manager = $entity_manager;
    }

    public function supports($data): bool
    {
        return is_subclass_of(get_class($data), AbstractEntity::class);
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
