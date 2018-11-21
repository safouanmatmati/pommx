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

namespace Pommx\Repository\Traits;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

use PommProject\Foundation\Session\Session;

use Pommx\Converter\PgEntity;
use Pommx\Repository\IdentityMapper;
use Pommx\MapProperties\MapPropertiesManager;
use Pommx\Relation\RelationsManager;
use Pommx\Fetch\FetcherManager;
use Pommx\Fetch\Annotation\Fetch;
use Pommx\Entity\AbstractEntity;

use Pommx\Tools\CheckIntegrityTrait;
use Pommx\Tools\Exception\ExceptionManager;

trait Entity
{
    use CheckIntegrityTrait;

    /**
     *
     * @var Serializer
     */
    protected static $serializer;

    /**
     * [private description]
     *
     * @var MapPropertiesManager
     */
    private $map_prop_manager;

    /**
     * [private description]
     *
     * @var RelationsManager
     */
    private $rel_entities_manager;

    /**
     *
     * @var IdentityMapper
     */
    private $cache_manager;

    /**
     * Returns entity from cache manager.
     *
     * @param  AbstractEntity|array $values
     * @return ?AbstractEntity
     */
    public function getEntityRef($values): ?AbstractEntity
    {
        if (false == is_array($values) && false == is_a($values, $this->getEntityClass())) {
            $this->getExceptionManager()->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Failed to retrieve entity reference.'.PHP_EOL
                    .'Invalid argument passed.'.PHP_EOL
                    .'["array", "%s"] expected, "%s" type found.',
                    $this->getEntityClass(),
                    gettype($values)
                ),
                \InvalidArgumentException::class
            );
        }

        $primary_key_def = $this->getStructure()->getPrimaryKey();

        if (false == is_array($values)) {
            $values = $values->extract($primary_key_def);
        }

        foreach ($primary_key_def as $name) {
            $values[$name] = $values[$name] ?? null;

            if (true == empty($values[$name])) {
                $this->getExceptionManager()->throw(
                    __TRAIT__,
                    __LINE__,
                    sprintf(
                        'Failed to retrieve entity reference.'.PHP_EOL
                        .'Primary key {"%s"} is missing.',
                        $name
                    )
                );
            }
        }

        if (true == is_array($values)) {
            // "parent::createEntity()" skip entity initializing treatments, useless here
            $entity = parent::createEntity($values);
        }

        // Retrieves entity from cache manager
        return $this
            ->getCacheManager()
            ->get($entity, $primary_key_def);
    }

    public function getEntityProxy($values): AbstractEntity
    {
        return $this->fetcher_manager->getEntityProxy($this->getEntityClass(), $values);
    }

    /**
     *  Creates entity.
     *
     * @param  array $values
     * @return AbstractEntity
     */
    public function createEntity(array $values = []): AbstractEntity
    {
        $entity = parent::createEntity();

        $entity = $this->preInitializeEntity($entity);
        $entity = $this->initializeEntity($entity, $values);

        return $entity;
    }

    /**
     * Retrieves entity upon values from cache, otherwise database, or create new one if not found.
     *
     * @param  array $values
     * @return AbstractEntity
     */
    public function entityFactory(array $values): AbstractEntity
    {
        // Creates
        $tmp = $this->createEntity($values);

        // Try to retrieve entity from cache
        if (false == is_null($entity = $this->getEntityRef($tmp))) {
            return $entity;
        }

        // Try to retrieve entity from database
        $values = $tmp->fields($this->getStructure()->getFieldNames());
        $values = array_filter($values);

        if (false == empty($values)
            && false == is_null($entity = $this->findOneFrom($values))
        ) {
            return $entity;
        }

        // returns new entity
        return $tmp;
    }

    /**
     * Call "entityFactory" method, depending on parameters.
     *
     * @see "entityFactory()"
     *
     * @param  array $parameters [description]
     * @return AbstractEntity             [description]
     */
    public static function staticEntityFactory(array $parameters): AbstractEntity
    {
        // Checks params definition validity
        $params_definitions = [
            'initiator'  => ['class' => AbstractEntity::class],
            'session'    => ['class' => Session::class],
            'class'      => ['string'],
            'parameters' => ['array']
        ];

        self::checkArrayAssocIntegrity(
            $parameters,
            $params_definitions,
            new ExceptionManager(),
            __TRAIT__,
            __LINE__
        );
        
        return $parameters['session']
            ->getRepository($parameters['class'])
            ->entityFactory($parameters['parameters']);
    }

    /**
     * Returns primary key definition depending on parameters.
     *
     * @param  array $parameters [description]
     * @return array             [description]
     */
    public static function staticPrimaryKey(array $parameters): array
    {
        // Checks params definition validity
        $params_definitions = [
            'entity'  => ['class' => AbstractEntity::class],
            'session' => ['class' => Session::class]
        ];

        self::checkArrayAssocIntegrity(
            $parameters,
            $params_definitions,
            new ExceptionManager(),
            __TRAIT__,
            __LINE__
        );

        return $parameters['session']
            ->getRepository(get_class($parameters['entity']))
            ->getStructure()
            ->getPrimaryKey();
    }

    /**
     * Pre initialize callback.
     *
     * @param  AbstractEntity $entity
     * @return AbstractEntity
     */
    protected function preInitializeEntity(AbstractEntity $entity): AbstractEntity
    {
        $this->callUnawareVisibility($entity, 'preInitialize', $this->getExceptionManager());

        return $entity;
    }

    /**
     * Initializes entity.
     *
     * Note: Called for all entities created, even by core process,
     * thanks to "self::createEntity()" & "PgEntity::cacheEntity()".
     *
     * @param  AbstractEntity $entity
     * @return Entity
     */
    protected function initializeEntity(AbstractEntity $entity, array $values = []): AbstractEntity
    {
        $this->callUnawareVisibility(
            $entity,
            'initialize',
            $this->fetcher_manager,
            $this->map_prop_manager,
            $this->rel_entities_manager,
            $this->getStructure()->getFieldNames(),
            $values
        );

        return $entity;
    }

    /**
     * Set entity status depending on its values.
     *
     * @param  AbstractEntity $entity
     * @return AbstractEntity
     */
    public function setEntityStatusAuto(AbstractEntity $entity): AbstractEntity
    {
        // Bypass if status already setted
        if (true == $entity->isStatus($entity::STATUS_MODIFIED | $entity::STATUS_TO_DELETE)) {
            return $entity;
        }

        $originals = $entity->fields($definiton = $this->getStructure()->getFieldNames());
        $actuals = $entity->extract($definiton);

        foreach ($originals as $key => $value) {
            $originals[$key] = $this->getSerializer()->serialize($value, 'json');
            if ($originals[$key] == 'null') {
                unset($originals[$key]);
            }
        }
        foreach ($actuals as $key => $value) {
            $actuals[$key] = $this->getSerializer()->serialize($value, 'json');
            if ($actuals[$key] == 'null') {
                unset($actuals[$key]);
            }
        }

        // If some values are defined and status is as default, changed it as "modified"
        if (true == $entity->isStatus($entity::STATUS_NONE) && false == empty($actuals)) {
            return $entity->setStatus([$entity::STATUS_MODIFIED => true]);
        }

        ksort($originals);
        ksort($actuals);

        // If originals data keys or value have changed
        if (array_diff_assoc($originals, $actuals) || array_diff_assoc($actuals, $originals)) {
            $primary_keys = array_flip($this->getStructure()->getPrimaryKey());
            $actuals_primary_keys = array_intersect_key($actuals, $primary_keys);
            $default_primary_keys = array_intersect_key($originals, $primary_keys);

            // If primary keys were defined but not anymore
            if (count($primary_keys) == count($default_primary_keys)
                && count($primary_keys) != count($actuals_primary_keys)
            ) {
                $entity->setStatus([$entity::STATUS_TO_DELETE => true]);
            } else {
                $entity->setStatus([$entity::STATUS_MODIFIED => true]);
            }
        }

        return $entity;
    }

    /**
     * Returns entity class name.
     * Alias of "getFlexibleEntityClass()" method.
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->getFlexibleEntityClass();
    }

    /**
     * Returns serializer instance
     *
     * @return Serializer
     */
    private function getSerializer(): Serializer
    {
        if (true == is_null(self::$serializer)) {
            $normalizer = new ObjectNormalizer();
            $normalizer->setCircularReferenceLimit(2);

            // Add Circular reference handler
            $normalizer->setCircularReferenceHandler(
                function ($object) {
                    if (true == is_a($object, AbstractEntity::class)) {
                        return $object->getHash();
                    }
                }
            );

            self::$serializer = new Serializer(
                [$normalizer],
                [new JsonEncoder()]
            );
        }

        return self::$serializer;
    }

    /**
     * Initializes trait
     *
     * @param  MapPropertiesManager    $map_prop_manager     [description]
     * @param  RelationsManager $rel_entities_manager [description]
     * @param  FetcherManager          $fetcher_manager      [description]
     * @return self                                          [description]
     */
    private function initializeEntityTrait(
        MapPropertiesManager $map_prop_manager,
        RelationsManager $rel_entities_manager,
        FetcherManager $fetcher_manager
    ): self {
        $this->map_prop_manager     = $map_prop_manager;
        $this->rel_entities_manager = $rel_entities_manager;
        $this->fetcher_manager      = $fetcher_manager;

        return $this;
    }

    /**
     * Initializes PgEntity converter with a new cache manager.
     *
     * @see PgEntity
     * @see IdentityMapper
     *
     * @param  Session $session [description]
     * @return self
     */
    private function initializePgEntityConverter(Session $session): self
    {
        // Replaces default "PgEntity", this one allows use of callbacks,
        // usefull for treatements before/after caching
        $converter = new PgEntity(
            $this->entity_class,
            $this->getStructure(),
            // Replaces default "IdentityMapper".
            // This one allows to replace cached entity (@see "insert()")
            new IdentityMapper($this->getExceptionManager())
        );

        $converter->setExceptionManager($this->getExceptionManager());

        $self = $this;

        $callbacks = [
            'preCacheEntityCallback' => function ($entity) use ($self) {
                return $self->preInitializeEntity($entity);
            },
            'postCacheEntityCallback' => function ($entity) use ($self) {
                $self->initializeEntity($entity);

                // If $entity was a proxy, it can be it anymore after being fetched from database
                $entity->setStatus([$entity::STATUS_PROXY => false]);

                return $entity;
            }
        ];

        $converter->initCallbacks($callbacks);

        $session
            ->getPoolerForType('converter')
            ->getConverterHolder()
            ->registerConverter(
                $this->entity_class,
                $converter,
                [$this->getStructure()->getRelation(), $this->entity_class],
                false
            );

        // Defines cache manager
        $this->cache_manager = $converter->getCacheManager();

        return $this;
    }

    /**
     * Returns the cache manager.
     *
     * @return IdentityMapper
     */
    public function getCacheManager(): IdentityMapper
    {
        return $this->cache_manager;
    }
}
