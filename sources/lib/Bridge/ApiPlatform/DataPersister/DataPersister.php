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

namespace PommX\Bridge\ApiPlatform\DataPersister;

use PommProject\Foundation\Pomm;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use PommX\Bridge\ApiPlatform\DataPersister\SubDataPersister\AbstractDataPersister;

use PommX\EntityManager\EntityManager;
use PommX\Entity\AbstractEntity;

final class DataPersister implements DataPersisterInterface
{
    /**
     *
     * @var string
     */
    private $namespace;

    /**
     * [private description]
     *
     * @var Pomm
     */
    private $pomm;

    /**
     *
     * @var EntityManager
     */
    private $entity_manager;

    /**
     * [private description]
     *
     * @var AbstractDataPersister[]
     */
    private $data_persisters = [];

    public function __construct(Pomm $pomm, string $namespace)
    {
        $this->pomm = $pomm;
        $this->entity_manager = $this->pomm->getDefaultSession()->getEntityManager();

        // Removes "\" at the end
        $this->namespace = preg_replace('/[\\\]+$/', '', $namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        if (true == in_array($resource_class = get_class($data), $this->data_persisters)) {
            if (true == is_null($this->data_persisters[$resource_class])) {
                return false;
            }

            return $this->data_persisters[$resource_class]->supports($data);
        }

        $data_persister = $this->getDataPersisterInstance($resource_class);

        $this->data_persisters[$resource_class] = $data_persister;

        if (true == is_null($this->data_persisters[$resource_class])) {
            return false;
        }

        return $data_persister->supports($data);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        $this->getDataPersister($data)->persist($data);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data)
    {
        $this->getDataPersister($data)->remove($data);
    }

    /**
     * Returns data persister depending on $data.
     *
     * @param  [type] $data
     * @return AbstractDataPersister
     */
    private function getDataPersister($data): AbstractDataPersister
    {
        return $this->data_persisters[get_class($data)];
    }

    /**
     * Returns data persister depending on $resource_class.
     * Try to load a specific one with path as :
     *  {current namespace}/{$resource_class short name}DataPersister.php
     * If it doesn't exists, return a 'DefaultDataPersister' instance.
     *
     * @param  string $resource_class
     * @return AbstractDataPersister|null
     */
    private function getDataPersisterInstance(string $resource_class): ?AbstractDataPersister
    {
        if (true == class_exists($resource_class)) {
            $ref = new \ReflectionClass($resource_class);

            if (true == $ref->isSubclassOf(AbstractEntity::class)) {
                $repository = $this->pomm->getDefaultSession()
                    ->getRepository($resource_class);

                $persister_class = $this->namespace . '\\' . $ref->getShortName().'DataPersister';
                if (true == class_exists($persister_class)) {
                    return new $persister_class($this->pomm, $this->entity_manager, $repository);
                }

                $persister_class = $this->namespace . '\\' . 'DefaultDataPersister';

                return new $persister_class($this->pomm, $this->entity_manager, $repository);
            }
        }

        return null;
    }
}
