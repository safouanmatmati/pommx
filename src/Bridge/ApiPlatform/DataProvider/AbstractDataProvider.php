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

namespace Pommx\Bridge\ApiPlatform\DataProvider;

use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;

use Symfony\Component\Serializer\SerializerInterface;

use PommProject\Foundation\Pomm;

use Pommx\Entity\AbstractEntity;

abstract class AbstractDataProvider implements
    SubresourceDataProviderInterface,
    RestrictedDataProviderInterface
{
    /**
     *
     * @var Pomm
     */
    protected $pomm;

    /**
     *
     * @var AbstractRepository
     */
    protected $repository;

    /**
     * [protected description]
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     *
     * @param Pomm $pomm
     */
    public function __construct(Pomm $pomm, SerializerInterface $serializer)
    {
        $this->pomm = $pomm;

        $this->serializer = $serializer;
    }

    /**
     * [supports description]
     *
     * @param  string      $resource_class
     * @param  string|null $operation_name
     * @param  array       $context
     * @return bool
     */
    public function supports(string $resource_class, string $operation_name = null, array $context = []): bool
    {
        return is_subclass_of($resource_class, AbstractEntity::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        die(__METHOD__);

    }
    /**
     * [getItem description]
     *
     * @param  string      $resource_class
     * @param  array       $fields
     * @param  string|null $operation_name
     * @param  array       $context
     * @return AbstractEntity|null
     */
    public function getItem(
        string $resource_class,
        array $primary_key,
        string $operation_name = null,
        array $context = []
    ): ?AbstractEntity {
        return $this
            ->repository
            ->findByPkFromQb(
                $primary_key,
                $this->getAllowedAttributes(true, $resource_class, $context),
                $context
            );
    }

    /**
     * [getCollectionQueryBuilder description]
     *
     * @param  string     $resource_class
     * @param  array|null $operation_name
     * @param  array      $context
     * @return array
     */

    public function getCollection(string $resource_class, string $operation_name = null, array $context = [])
    {
        return $this->repository
            ->findAllFromQb(
                $this->getAllowedAttributes(true, $resource_class, $context),
                null,
                $context
            );
    }

    /**
     * Returns allowed attributes list, depending on context because of serializer.
     *
     * @param  bool   $is_collection  [description]
     * @param  string $resource_class [description]
     * @param  array  $context        [description]
     * @return array                  [description]
     */
    protected function getAllowedAttributes(bool $is_collection, string $resource_class, array $context): array
    {
        $pattern = $this->repository->createEntity();
        $pattern = true == $is_collection ? [$pattern] : $pattern;
        $template = $this->serializer->normalize($pattern, $resource_class, $context);

        return array_keys($is_collection ? ($template[0] ?? []) : $template);
    }
}
