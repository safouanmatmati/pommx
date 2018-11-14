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

namespace PommX\Bridge\ApiPlatform\DataProvider;

use PommProject\Foundation\Pomm;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\SerializerAwareDataProviderInterface;
use ApiPlatform\Core\DataProvider\SerializerAwareDataProviderTrait;

use PommX\Bridge\ApiPlatform\DataProvider\SubDataProvider\AbstractDataProvider;

use PommX\Repository\QueryBuilder;
use PommX\Entity\AbstractEntity;
use PommX\EntityManager\EntityManager;

class DataProvider implements
    ItemDataProviderInterface,
    ContextAwareCollectionDataProviderInterface,
    SubresourceDataProviderInterface,
    RestrictedDataProviderInterface,
    SerializerAwareDataProviderInterface
{
    use SerializerAwareDataProviderTrait;

    /**
     *
     * @var string
     */
    private $namespace;

    /**
     * [private description]
     *
     * @var array
     */
    private $data_providers = [];

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
     * [__construct description]
     *
     * @param string $namespace
     */
    public function __construct(Pomm $pomm, string $namespace)
    {
        $this->pomm = $pomm;
        $this->entity_manager = $this->pomm->getDefaultSession()->getEntityManager();

        // Removes "\" at the end
        $this->namespace = preg_replace('/[\\\]+$/', '', $namespace);
    }

    /**
     * [supports description]
     *
     * @param  string      $resource_class
     * @param  string|null $operation_name
     * @param  array       $context
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function supports(string $resource_class, string $operation_name = null, array $context = []): bool
    {
        if (true == in_array($resource_class, $this->data_providers)) {
            if (true == is_null($this->data_providers[$resource_class])) {
                return false;
            }

            return $this->data_providers[$resource_class]->supports($resource_class, $operation_name, $context);
        }

        $data_provider =  $this->getDataProviderInstance($resource_class);

        $this->data_providers[$resource_class] = $data_provider;

        if (true == is_null($this->data_providers[$resource_class])) {
            return false;
        }

        return $this->data_providers[$resource_class]->supports($resource_class, $operation_name, $context);
    }

    public function getItem(string $resource_class, $identifier, string $operation_name = null, array $context = [])
    {
        $item = $this
            ->getDataProvider($resource_class)
            ->getItem($resource_class, [$identifier], $operation_name, $context);

        if (true == is_object($item) && true == is_a($item, AbstractEntity::class)) {
            $this->entity_manager->persist($item, true);
        }

        return $item;
    }

    public function getCollection(string $resource_class, string $operation_name = null, array $context = [])
    {
        return $this->getDataProvider($resource_class)
            ->getCollection($resource_class, $operation_name, $context);
    }

    public function getSubresource(
        string $resource_class,
        array $identifiers,
        array $context,
        string $operation_name = null
    ) {
        die(__METHOD__);

        // return $this->getDataProvider($resource_class)
        //     ->getSubresource($resource_class, $identifiers, $context, $operation_name)
        //     ->applyExtensions(
        //         true,
        //         true,
        //         [
        //             'resource_class' => $resource_class,
        //             'identifiers' => $identifiers,
        //             'operation_name' => $operation_name,
        //             'context' => $context
        //         ]
        //     );
    }

    private function getDataProvider(string $resource_class): AbstractDataProvider
    {
        return $this->data_providers[$resource_class];
    }

    /**
     * [getDataProviderInstance description]
     *
     * @param  string $resource_class
     * @return AbstractDataProvider|null
     */
    private function getDataProviderInstance(string $resource_class): ?AbstractDataProvider
    {
        if (true == class_exists($resource_class)) {
            $ref = new \ReflectionClass($resource_class);

            if (true == $ref->isSubclassOf(AbstractEntity::class)) {
                $repository = $this->pomm->getDefaultSession()
                    ->getRepository($resource_class);

                $provider_class = $this->namespace . '\\' . $ref->getShortName().'DataProvider';
                if (true == class_exists($provider_class)) {
                    return new $provider_class($this->pomm, $repository, $this->getSerializer());
                }

                $provider_class = $this->namespace . '\\' . 'DefaultDataProvider';

                return new $provider_class($this->pomm, $repository, $this->getSerializer());
            }
        }

        return null;
    }
}
