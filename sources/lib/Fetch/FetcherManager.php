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

namespace PommX\Fetch;

use PommProject\Foundation\Pomm;
use Doctrine\Common\Annotations\Reader;

use PommX\Fetch\Annotation\Fetch;

use PommX\Entity\AbstractEntity;
use PommX\Repository\AbstractRepository;
use PommX\Repository\Join;

use PommX\Tools\Exception\ExceptionManagerInterface;
use PommX\Tools\CheckIntegrityTrait;
use PommX\Tools\UnawareVisibilityTrait;

// TODO test if a "RIGHT JOIN" (instead a "LEFT" one) whitout a "FILTER" clause is more efficient.
// TODO create a proxy for LAZY & EXTRA_LAZY with 'to' parameter
class FetcherManager
{
    use CheckIntegrityTrait;
    use UnawareVisibilityTrait;

    /**
     * [private description]
     *
     * @var Pomm
     */
    private $pomm;

    /**
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    /**
     *
     * @var Reader
     */
    private $reader;

    /**
     * [private description]
     *
     * @var array
     */
    private $annotations = [];

    /**
     * [private description]
     *
     * @var array
     */
    private $joins = [];

    /**
     * [__construct description]
     *
     * @param Reader $reader [description]
     */
    public function __construct(Pomm $pomm, ExceptionManagerInterface $exception_manager, Reader $reader)
    {
        $this->pomm              = $pomm;
        $this->exception_manager = $exception_manager;
        $this->reader            = $reader;
    }

    /**
     * Returns properties annotations.
     *
     * @param  string $class [description]
     * @return array           [description]
     */
    public function getAnnotations(string $class): array
    {
        if (true == is_null($this->annotations[$class] ?? null)) {
            $this->loadAnnotations($class);
        }

        return $this->annotations[$class];
    }

    /**
     * Returns property annotation.
     *
     * @param  string $class    [description]
     * @param  string $property [description]
     * @return array|null           [description]
     */
    public function getAnnotation(string $class, string $property): ?array
    {
        if (true == is_null($this->annotations[$class])) {
            $this->loadAnnotations($class);
        }

        return $this->annotations[$class][$property] ?? null;
    }

    /**
     * Returns annotations of properties defined as Fetch::MODE_PROXY.
     *
     * @param  string $class [description]
     * @return array           [description]
     */
    public function getProxyProperties(string $class): array
    {
        return $this->getPropertiesByMode($class, Fetch::MODE_PROXY);
    }

    /**
     * Returns annotations of properties defined as Fetch::MODE_LAZY.
     *
     * @param  string $class [description]
     * @return array           [description]
     */
    public function getLazyProperties(string $class): array
    {
        return $this->getPropertiesByMode($class, Fetch::MODE_LAZY);
    }

    /**
     * Returns annotations of properties defined as Fetch::MODE_EXTRA_LAZY.
     *
     * @param  string $class [description]
     * @return array           [description]
     */
    public function getExtraLazyProperties(string $class): array
    {
        return $this->getPropertiesByMode($class, Fetch::MODE_EXTRA_LAZY);
    }

    /**
     * Returns annotations of properties defined as Fetch::MODE_JOIN.
     *
     * @param  string $class [description]
     * @return array           [description]
     */
    public function getJoinProperties(string $class): array
    {
        return $this->getPropertiesByMode($class, Fetch::MODE_JOIN);
    }

    /**
     * Returns annotations of $class properties.
     *
     * @param  string $class [description]
     * @return array           [description]
     */
    public function getProperties(string $class): array
    {
        return $this->getAnnotations($class);
    }

    /**
     * Returns annotations of properties depending on $mode.
     *
     * @param  string $class [description]
     * @return array           [description]
     */
    private function getPropertiesByMode(string $class, string $mode): array
    {
        $properties = [];

        foreach ($this->getAnnotations($class) as $property => $property_annotation) {
            if ($mode == $property_annotation['mode']) {
                $properties[$property] = $property_annotation;
            }
        }

        return $properties;
    }

    /**
     * Returns annotation.
     * Checks annotation integrity.
     *
     * @param  array               $data     [description]
     * @param  \ReflectionProperty $property [description]
     * @return array       [description]
     */
    private function checkAnnotationIntegrity(Fetch $annotation, \ReflectionProperty $property): array
    {
        $data = array_filter((array) $annotation);

        // params definition validity
        $params_definitions = [
            'attributes' => [
                'mode' => [
                    'string',
                    'enum' => [Fetch::MODE_JOIN, Fetch::MODE_PROXY, Fetch::MODE_LAZY, Fetch::MODE_EXTRA_LAZY]
                ],
                'from'     => ['string', 'NULL'],
                'to'       => ['string', 'NULL'],
                'join'     => ['array', 'NULL'],
                'map'      => ['array', 'NULL'],
                'callback' => ['callable', 'array', 'NULL']
            ],
            'join attribute rules' => [
                'key'   => ['string'],
                'value' => ['string'],
                'count' => 1
            ],
            'attributes '. Fetch::MODE_PROXY => [
                'values' => ['array', 'NULL'],
            ],
            'dependencies '. Fetch::MODE_JOIN => [
                'mode' => [
                    'at_least' => ['from', 'to', 'join', 'callback'],
                ],
            ],
            'dependencies' => [
                'mode' => [
                    'max_one'  => ['from', 'to', 'join', 'callback'],
                ],
                'map' => [
                    'at_least' => ['from', 'to', 'join']
                ],
            ]
        ];

        $message = sprintf(
            'Failed to load "@Fetch" annotation from "%s::$%s".',
            $property->getDeclaringClass()->getName(),
            $property->getName()
        );

        try {
            if ($data['mode'] == Fetch::MODE_PROXY) {
                $params_definitions['attributes'] = array_merge(
                    $params_definitions['attributes'],
                    $params_definitions['attributes '. Fetch::MODE_PROXY]
                );
            }

            self::checkArrayAssocIntegrity(
                $data,
                $params_definitions['attributes'],
                $this->exception_manager
            );

            self::checkArrayIntegrity(
                $data['join'] ?? null,
                $params_definitions['join attribute rules'],
                false,
                $this->exception_manager
            );

            if ($data['mode'] == Fetch::MODE_JOIN) {
                $params_definitions['dependencies'] = array_merge(
                    $params_definitions['dependencies'],
                    $params_definitions['dependencies '. Fetch::MODE_JOIN]
                );
            }

            self::checkArrayAssocDependencies(
                $data,
                $params_definitions['dependencies'],
                $this->exception_manager
            );

        } catch (\Exception $e) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                $message,
                null,
                $e
            );
        }

        return $data;
    }

    /**
     * Returns annotations.
     * Checks annotation integrity.
     *
     * @param  string $class [description]
     * @return array         [description]
     */
    private function loadAnnotations(string $class): array
    {
        // We use "ReflectionClass" instead of "InheritedReflectionClass",
        // because "InheritedReflectionClass::getTraits()" method will not respect priority.
        $ref             = new \ReflectionClass($class);
        $parents         = $traits = [];
        $is_parent_class = false;

        // Use class, trait, parent or parent trait class annotations
        do {
            // Do not retrieves private properties from inherited parent class or their parent class trait
            $ref_properties = $is_parent_class == false
                ? $ref->getProperties()
                : $ref->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

            // Loads property "@Fetch" definition if nothing has been found yet.
            foreach ($ref_properties as $property) {
                if (true == is_null($this->annotations[$class][$name = $property->getName()] ?? null)) {
                    $prop_fetch_anno = $this->reader->getPropertyAnnotation($property, Fetch::class);

                    if (false == is_null($prop_fetch_anno)) {
                        $prop_fetch_anno = $this->checkAnnotationIntegrity(
                            $prop_fetch_anno,
                            $property
                        );
                        $this->annotations[$class][$name] = $prop_fetch_anno;
                    }
                }
            }

            // Retrieves traits, only new ones
            foreach ($ref->getTraits() as $trait) {
                $traits[$trait->getName()] = $trait;
            }

            // Retrieves parent class
            if (true == is_object($parent = $ref->getParentClass())) {
                $parents[$parent->getName()] = $parent;
            };

            // Defines next class to search in.
            // It can be the a new trait.
            if (false == is_object($ref = current($traits))) {
                $is_parent_class = true;
                $ref = current($parents);
                next($parents);
            }

            next($traits);
        } while ($ref);

        return $this->annotations[$class] = $this->annotations[$class] ?? [];
    }

    /**
     * Set properties defined as "proxy" with a corresponding one proxy.
     *
     * @param  AbstractEntity $entity [description]
     * @return [type]                 [description]
     */
    public function defineProxies(AbstractEntity $entity)
    {
        $entity_repo   = $this->pomm->getDefaultSession()->getRepository($class = get_class($entity));
        $entity_values = $entity->fields();

        foreach ($this->getProxyProperties($class) as $property => $annotation) {
            if (false == is_null($this->getUnawareVisibility($entity, $property))
                || true == $this->isPropertyFetched($entity, $property)
            ) {
                continue;
            }

            $this->propertyFetched($entity, $property);

            $related_class    = $annotation['to'];
            $proxy_repository = $this->pomm->getDefaultSession()->getRepository($related_class);

            try {
                $mapping = $this->getMapping(
                    $entity_repo,
                    $proxy_repository,
                    true,
                    $annotation['map'] ?? null
                );
            } catch (\Exception $e) {
                $this->exception_manager->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to define proxy for "%s::$%s"',
                        $class,
                        $property
                    ),
                    null,
                    $e
                );
            }

            // Filter
            $mapping = $annotation['map']
                ? array_intersect_key($mapping, array_flip($annotation['map']))
                : $mapping;

            $values = [];

            foreach ($mapping as $foreign_key => $pk) {
                if (true == isset($values[$pk])) {
                    $this->exception_manager->throw(
                        self::class,
                        __LINE__,
                        sprintf(
                            'Failed to define proxy for "%s::$%s"'.PHP_EOL
                            .'"%s" structure have several foreign key related to same "%s" structure primary key.'.PHP_EOL
                            .'Use "map" to define a specific one between {"%s"}.',
                            $class,
                            $property,
                            $class,
                            $proxy_repository->getEntityClass(),
                            join('", "', array_keys(array_intersect($mapping, [$pk])))
                        ),
                        null,
                        $e
                    );
                }

                $values[$pk] = $entity_values[$foreign_key] ?? null;
            }

            if (false == is_null($annotation['values'] ?? null)) {
                $values = array_merge($values, $annotation['values']);
            }

            $values   = array_filter($values);
            $proxy_pk = $proxy_repository->getStructure()->getPrimaryKey();

            // Proxy can't be created without all primary key value defined
            if (count($proxy_pk) != count(array_intersect_key(array_flip($proxy_pk), $values))) {
                continue;
            }

            $this->setUnawareVisibility($entity, $property, $this->getEntityProxy($related_class, $values));
        }

        return $entity;
    }

    /**
     * Returns a new entity proxy or its reference if it has previously stored in the cache manager,
     * in that case, it can be the "real" entity reference.
     *
     * A proxy is a new created entity instance, defined as existing in database
     * and with "AbstractEntity::STATUS_PROXY" status.
     * All modifications applied will be persisted (if the entity is persisted then flushed).
     *
     * @param  AbstractEntity|array $values
     * @return AbstractEntity
     */
    public function getEntityProxy(string $class, $values): AbstractEntity
    {
        $entity_repo = $this->pomm->getDefaultSession()->getRepository($class);

        if (true == is_null($entity = $entity_repo->getEntityRef($values))) {
            $entity = is_array($values) ? $entity_repo->createEntity($values) : $values;

            // Cache it
            $entity_repo->getCacheManager()->fetch(
                $entity,
                $entity_repo->getStructure()->getPrimaryKey()
            );

            $entity->setStatus([$entity::STATUS_EXIST => true, $entity::STATUS_PROXY => true]);
        }

        return $entity;
    }

    /**
     * Fetch property defined as "lazy".
     *
     * @param AbstractEntity $entity   [description]
     * @param string         $property [description]
     */
    public function fetchProperty(AbstractEntity $entity, string $property): void
    {
        if (true == $this->isPropertyFetched($entity, $property)) {
            return ;
        }

        $this->propertyFetched($entity, $property);

        if (false == empty($annotation = $this->getAnnotation($class = get_class($entity), $property))) {
            if (Fetch::MODE_LAZY == $annotation['mode']) {
                $entity_repo = $this->pomm->getDefaultSession()->getRepository($class);

                foreach (array_keys($lazy_properties = $this->getLazyProperties($class)) as $lazy_property) {
                    $this->propertyFetched($entity, $lazy_property);
                }

                $entity = $entity_repo->findByPkFromQb(
                    $entity->fields($entity_repo->getStructure()->getPrimaryKey()),
                    array_keys($lazy_properties)
                );

                return;
            } elseif (Fetch::MODE_EXTRA_LAZY == $annotation['mode']) {
                $entity_repo = $this->pomm->getDefaultSession()->getRepository($class);

                $entity = $entity_repo->findByPkFromQb(
                    $entity->fields($entity_repo->getStructure()->getPrimaryKey()),
                    [$property]
                );

                return;
            }
        }

        if (false == $entity->isStatus($entity::STATUS_PROXY)) {
            return;
        }

        // Load entity data from database
        $entity_repo = $this->pomm->getDefaultSession()->getRepository($class);
        $values = $entity->fields($entity_repo->getStructure()->getPrimaryKey());
        $entity = $entity_repo->findByPkFromQb($values);
        $entity->setStatus([$entity::STATUS_PROXY => false]);
    }

    public function initNewJoin(
        string $class,
        string $property,
        string $source_class,
        string $related_class,
        bool $source_to_related,
        array $filter_mapping = null
    ): Join {
        $source_repo  = $this->pomm->getDefaultSession()->getRepository($source_class);
        $related_repo = $this->pomm->getDefaultSession()->getRepository($related_class);

        $join = new Join($this->exception_manager);
        $join
            ->setSource($source_repo->getStructure()->getRelation())
            ->setRelated($related_repo->getStructure()->getRelation())
            ->setType(Join::TYPE_LEFT);

        try {
            $mapping = $this->getMapping(
                $source_repo,
                $related_repo,
                $source_to_related,
                $filter_mapping
            );
        } catch (\Exception $e) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to create JOIN for "%s::$%s"',
                    $class,
                    $property
                ),
                null,
                $e
            );
        }

        $join->setMappedCondition($mapping);

        return $join;
    }

    public function getJoin(string $class, string $property): ?Join
    {
        if (true == isset($this->joins[$class][$property])) {
            return $this->joins[$class][$property];
        }

        $this->defineJoin($class, $property, $this->getAnnotation($class, $property));

        return $this->joins[$class][$property];
    }

    public function getJoins(string $class, array $properties): array
    {
        $joins = [];

        foreach ($properties as $property) {
            if (false == is_null($join = $this->getJoin($class, $property))) {
                $joins[$property] = $join;
            }
        }

        return $joins;
    }

    /**
     * Define joins for a property with @Fetch::MODE_JOIN or @Fetch::MODE_LAZY annotation
     *
     * @param string $class [description]
     */
    private function defineJoin($class, $property, $annotation): void
    {
        if (true == isset($this->joins[$class][$property])) {
            return ;
        }

        if (false == in_array($annotation['mode'], [Fetch::MODE_JOIN, Fetch::MODE_LAZY, Fetch::MODE_EXTRA_LAZY])) {
            $this->joins[$class][$property] = null;
            return ;
        }
        if (true == isset($annotation['callback'])) {
            try {
                $join = $this->getJoinFromCallback(
                    $class,
                    $property,
                    $annotation['callback']
                );
            } catch (\Exception $e) {
                $this->exception_manager->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to define JOIN from callback for "%s::$%s" property.',
                        $class,
                        $property
                    ),
                    null,
                    $e
                );
            }
        } elseif (true == isset($annotation['join'])) {
            $inter_class   = key($annotation['join']);
            $related_class = current($annotation['join']);

            $join = $this->initNewJoin(
                $class,
                $property,
                $class,
                $inter_class,
                false,
                $annotation['map'] ?? null
            );

            $related_join = $this->initNewJoin(
                $class,
                $property,
                $related_class,
                $inter_class,
                false,
                $annotation['map'] ?? null
            );
            $related_join->invert();

            $join->addJoin($related_join);

            $alias = $related_join->getRelatedAlias();

            $join->setField(
                $property,
                sprintf(
                    "array_agg(%s) FILTER (WHERE %s)",
                    $alias,
                    join(
                        ' AND ',
                        array_map(
                            function ($val) use ($alias) {
                                return sprintf("%s.%s != ''::text", $alias, $val);
                            },
                            $this->pomm->getDefaultSession()->getRepository($class)->getStructure()->getPrimaryKey()
                        )
                    )
                ),
                sprintf("%s[]", $related_class)
            );
        } elseif (true == isset($annotation['from'])) {
            $join = $this->initNewJoin(
                $class,
                $property,
                $class,
                $annotation['from'],
                false,
                $annotation['map'] ?? null
            );

            $alias = $join->getRelatedAlias();

            $join->setField(
                $property,
                sprintf(
                    "array_agg(%s) FILTER (WHERE %s)",
                    $alias,
                    join(
                        ' AND ',
                        array_map(
                            function ($val) use ($alias) {
                                return sprintf("%s.%s != ''::text", $alias, $val);
                            },
                            $this->pomm->getDefaultSession()->getRepository($class)->getStructure()->getPrimaryKey()
                        )
                    )
                ),
                sprintf("%s[]", $annotation['from'])
            );
        } elseif (true == isset($annotation['to'])) {
            $join = $this->initNewJoin(
                $class,
                $property,
                $class,
                $annotation['to'],
                true,
                $annotation['map'] ?? null
            );

            $join->setField(
                $property,
                $join->getRelatedAlias(),
                $annotation['to']
            );
        }

        $this->joins[$class][$property] = $join ?? null;
    }

    /**
     * Return mapping used to create proxy instance or join SQL clause.
     *
     * @param  string             $class        [description]
     * @param  string             $property     [description]
     * @param  AbstractRepository $source_repo  [description]
     * @param  AbstractRepository $related_repo [description]
     * @throws \Exception
     * @return array                            [description]
     */
    private function getMapping(
        AbstractRepository $source_repo,
        AbstractRepository $related_repo,
        bool $source_to_related,
        array $filter_mapping = null
    ): array {
        if (false == $source_to_related) {
            $tmp          = $source_repo;
            $source_repo  = $related_repo;
            $related_repo = $tmp;
        }

        $mapping = $source_repo->getStructure()->getForeignKeyRelatedTo($related_repo->getStructure());

        if (true == empty($mapping)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to retrieve mapping.'.PHP_EOL
                    .'"%s" structure doesn\'t have any foreign key related to "%s" structure.',
                    $source_repo->getEntityClass(),
                    $related_repo->getEntityClass()
                )
            );
        }

        // Keep mapping keys contained in $filter_mapping if it has right filters.
        if (false == empty($filter_mapping = array_intersect_key(array_flip($filter_mapping ?? []), $mapping))) {
            $mapping = array_intersect_key($mapping, $filter_mapping);
        }

        // If mapping contains several foreign key related to the same primary key
        $tmp = [];
        foreach ($mapping as $foreign_key => $pk) {
            if (true == isset($tmp[$pk])) {
                $this->exception_manager->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to retrieves mapping'.PHP_EOL
                        .'"%s" structure have several foreign key related to same "%s" structure primary key.'.PHP_EOL
                        .'Use "map" to define a specific one between {"%s"}.',
                        $source_repo->getEntityClass(),
                        $related_repo->getEntityClass(),
                        join('", "', array_keys(array_intersect($mapping, [$pk])))
                    )
                );
            }

            $tmp[$pk] = $foreign_key;
        }

        if (false == $source_to_related) {
            $mapping = array_flip($mapping);
        }

        return $mapping;
    }

    /**
     * [isPropertyFetched description]
     *
     * @param  AbstractEntity $entity   [description]
     * @param  string         $property [description]
     * @return bool                     [description]
     */
    private function isPropertyFetched(AbstractEntity $entity, string $property): bool
    {
        return (bool) ($entity->getSandbox(self::class)[$property] ?? null);
    }

    /**
     * [propertyFetched description]
     *
     * @param  AbstractEntity $entity   [description]
     * @param  string         $property [description]
     * @return self                     [description]
     */
    private function propertyFetched(AbstractEntity $entity, string $property): self
    {
        $entity->getSandbox(self::class)[$property] = true;
        return $this;
    }

    private function getJoinFromCallback(
        string $class,
        string $property,
        $callback
    ): Join {
        try {
            $parameters = [
                'pomm'     => $this->pomm,
                'class'    => $class,
                'property' => $property
            ];

            if (true == is_array($callback) && true == isset($callback['args'])) {
                $parameters = $parameters + $callback['args'];
                unset($callback['args']);
            }

            $join = call_user_func($callback, $parameters);
        } catch (\Exception $e) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to call callback',
                    $class,
                    $property
                ),
                null,
                $e
            );
        }

        self::checkIntegrity(
            'callback',
            $join,
            ['class' => Join::class],
            $this->exception_manager
        );

        return $join;
    }
}
