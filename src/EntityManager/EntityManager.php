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

namespace Pommx\EntityManager;

use Doctrine\Common\Annotations\Reader;

use PommProject\Foundation\Client\Client;
use PommProject\Foundation\Session\Connection;
use PommProject\Foundation\Session\Session;

use Pommx\Entity\AbstractEntity;
use Pommx\MapProperties\MapPropertiesTrait;
use Pommx\Relation\RelationTrait;

use Pommx\Repository\Layer\AbstractLayer;
use Pommx\Repository\Layer\GlobalTransaction;

use Pommx\EntityManager\Annotation\CascadePersist;
use Pommx\EntityManager\Annotation\CascadeDelete;
use Pommx\EntityManager\Annotation\CascadeDeletes;

use Pommx\Tools\Exception\ExceptionManagerInterface;
use Pommx\Tools\CheckIntegrityTrait;
use Pommx\Tools\InheritedReflectionClass;

class EntityManager extends GlobalTransaction
{
    use CheckIntegrityTrait;

    /**
     * [private description]
     *
     * @var array
     */
    private $deletes_mapping = [];

    /**
     *
     * @var AbstractLayer[]
     */
    private $layers = [];

    /**
     *
     * @var string[AbstractEntity[]]
     */
    public $persisted = [];

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
     * [__construct description]
     *
     * @param ExceptionManagerInterface $exception_manager [description]
     * @param Reader                    $reader            [description]
     */
    public function __construct(
        ExceptionManagerInterface $exception_manager,
        Reader $reader
    ) {
        $this->exception_manager = $exception_manager;
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientType()
    {
        return 'entity_manager';
    }

    /**
     * {@inheritdoc}
     */
    public function getClientIdentifier()
    {
        return self::class;
    }

    /**
     * Checks if an entity is persisted.
     *
     * @param  AbstractEntity $entity
     * @return bool
     */
    public function contains(AbstractEntity $entity): bool
    {
        return false == is_null($key = $this->getPersistedId($entity))
            && isset($this->persisted[$key]);
    }

    /**
     * Clear entity manager from persisted entities.
     *
     * @param  AbstractEntity|AbstractEntity[]|null $entity
     * @return self
     */
    public function clear($entities = null): self
    {
        $entities = $entities ?? $this->persisted;

        if (true == is_array($entities)) {
            foreach ($entities as $entity) {
                if (false == is_a($entity, AbstractEntity::class)) {
                    break;
                    $type = gettype($entity);
                }

                if (false == $this->contains($entity)) {
                    unset($this->persisted[$this->getPersistedId($entity)]);
                }
            }
        } elseif (false == is_a($entities, AbstractEntity::class)) {
            $type = gettype($entities);
        } elseif (false == $this->contains($entity)) {
            unset($this->persisted[$this->getPersistedId($entity)]);
        }

        if (true == isset($type)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to clear entity manager'.PHP_EOL
                    .'Invalid argument'.PHP_EOL
                    .'[%s, %s[], null] expected, "%s" found.',
                    AbstractEntity::class,
                    AbstractEntity::class,
                    $type
                )
            );
        }

        return $this;
    }

    /**
     * Persists entities.
     * Cascade persist on their related entities by default.
     * Returns persisted entities.
     *
     * @param  AbstractEntity|AbstractEntity[] $entities
     * @return AbstractEntity[]
     */
    public function persist($entities, bool $cascade = null): array
    {
        return $this->travel($entities, true, $cascade);
    }

    /**
     * Returns persisted entities.
     *
     * @return AbstractEntity[]
     */
    public function getPersisted(): array
    {
        $persisted  = [];
        foreach ($this->persisted as $key => $entities) {
            $persisted[$key] = $entities[$key];
        }

        return $persisted;
    }

    /**
     * Returns entities traveled depending on $cascade.
     * Persist $entities founded if $persist = true.
     * Cascade "travel()" if $cascade != false.
     *
     * @param  AbstractEntity|AbstractEntity[] $entities
     * @param  bool                            $persist
     * @param  bool|null                       $cascade
     * @param  array                           $traveled
     * @param  integer                         $depth
     * @return AbstractEntity[]
     */
    private function travel(
        $entities,
        bool $persist,
        bool $cascade = null,
        array $traveled = [],
        int $depth = 0
    ): array {
        $depth++;
        if (true == is_array($entities)) {
            foreach ($entities as $entity) {
                if (true == is_a($entity, AbstractEntity::class)
                    && (1 === $depth || (false !== $cascade && true == $this->cascadePersistEnabled($entity)))
                ) {
                    $traveled = array_merge(
                        $traveled,
                        $this->travel($entity, $persist, $cascade, $traveled, $depth)
                    );
                }
            }

            return $traveled;
        }

        $entity = $entities;

        if (false == is_object($entity) || false == is_a($entity, AbstractEntity::class)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to travel entity.'.PHP_EOL.'"%s" type founded, "%s" expected.',
                    (is_object($entity) ? get_class($entity) : gettype($entity)),
                    AbstractEntity::class
                )
            );
        }

        // Persists an entity if it isn't done yet
        if (true == $persist && false == $this->contains($entity)) {
            $persisted_key = $this->generatePersistedId($entity);
            $this->persisted[$persisted_key] = [$persisted_key => $entity];
        }
        $key = $entity->getHash();

        // Prevents circular call - Exits if the entity has already been traveled
        if (true == array_key_exists($entity->getHash(), $traveled)) {
            return $traveled;
        }

        $traveled[$key] = $entity;

        if (false === $cascade) {
            return $traveled;
        }

        // Persists related entities
        foreach ($entity->extract() as $name => $value) {
            if (true == is_array($value)
                || (true == is_object($value) && true == is_a($value, AbstractEntity::class)
                && true == $this->cascadePersistEnabled($value)
                && true == $this->cascadePersistEnabled($entity, $name))
            ) {
                $traveled = array_merge(
                    $traveled,
                    $this->travel($value, $persist, $cascade, $traveled, $depth)
                );
            }
        }

        if (true == $persist) {
            $this->persisted[$persisted_key = $this->getPersistedId($entity)] = array_merge(
                $this->persisted[$persisted_key],
                $traveled
            );
        }

        return $traveled;
    }

    /**
     * Indicates if a class or one of its properties has to be persisted on cascade.
     *
     * @param  AbstractEntity $entity
     * @return bool
     */
    private function cascadePersistEnabled(AbstractEntity $entity, string $property = null): bool
    {
        $definition = $this->getAnnotations(get_class($entity))['cascade_persist'];

        return $property ? ($definition['properties'][$property] ?? true) : $definition['self'];
    }

    /**
     * Returns annotations for a given class.
     *
     * @param  string $class
     * @return array
     */
    private function getAnnotations(string $class): array
    {
        $this->annotations[$class] = $this->annotations[$class]
            ?? $this->loadAnnotations($class);

        return $this->annotations[$class];
    }

    /**
     * Returns "@CascadeDeletes" annotation
     * Checks annotation integrity.
     *
     * @param  ReflectionProperty $ref [description]
     * @return array                   [description]
     */
    private function loadCascadeDeletesAnnotation(\ReflectionClass $ref): array
    {
        $annotation = $this->reader->getClassAnnotation($ref, CascadeDeletes::class) ?? [];

        if (true == empty($annotation)) {
            return [];
        }

        $params = (array) $annotation;

        // params definition validity
        $params_definitions = [
            'level1' => [
                'list' => ['array']
            ],
            'level2' => [
                'name'  => ['string', 'NULL'],
                'class' => ['string', 'NULL'],
                'map'   => ['string', 'NULL']
            ],
            'dependencies' => [
                'class' => 'map',
                'map'   => 'class'
            ]
        ];

        self::checkArrayAssocIntegrity(
            $params,
            $params_definitions['level1'],
            $this->exception_manager,
            self::class,
            __LINE__
        );

        $values = [];

        foreach ($params['list'] as $data) {
            self::checkArrayAssocIntegrity(
                $data,
                $params_definitions['level2'],
                $this->exception_manager,
                self::class,
                __LINE__
            );

            self::checkArrayAssocDependencies(
                $data,
                $params_definitions['dependencies'],
                $this->exception_manager,
                self::class,
                __LINE__
            );

            if (false == is_null($data['class'] ?? null)) {
                // Throws exception if class doesn't exists
                new InheritedReflectionClass($data['class']);
            }

            // "name" defined allows to override parent definition
            if (false == is_null($data['name'] ?? null)) {
                $values[$data['name']] = $data;
            } else {
                $values[] = $data;
            }
        }

        return $values;
    }

    /**
     * Loads annotations for a given class.
     *
     * Priority is given to current class annotations then its traits, parent class, parent traits, etc...
     * If a property annotations can be define from a class annotation (see CascadeDeletes)
     *
     * @param  string $class
     * @return array
     */
    private function loadAnnotations(string $class): array
    {
        if (true == array_key_exists($class, $this->annotations)) {
            return $this->annotations[$class];
        }

        $this->annotations[$class] = [
            'cascade_persist' => ['self' => null, 'properties' => []],
            'cascade_deletes' => []
        ];

        // We use "ReflectionClass" instead of "InheritedReflectionClass",
        // because "InheritedReflectionClass::getTraits()" method will not respect priority.
        $ref             = new \ReflectionClass($class);
        $parents         = $traits = $prop_del_anno_loaded = [];
        $is_parent_class = false;

        // Use class, trait, parent or parent trait class annotations
        do {
            // Load class "@CascadePersist" definition if nothing has been found yet.
            if (true == is_null($this->annotations[$class]['cascade_persist']['self'])) {
                $class_persist_anno = $this->reader->getClassAnnotation($ref, CascadePersist::class);

                if (false == is_null($class_persist_anno)) {
                    $this->annotations[$class]['cascade_persist']['self'] = $class_persist_anno->cascade();
                }
            }

            // Loads class "@CascadeDeletes" definition
            $cascade_deletes_list = $this->loadCascadeDeletesAnnotation($ref);

            // Do not retrieves private properties from inherited parent class or their parent class trait
            $ref_properties = $is_parent_class == false
                ? $ref->getProperties()
                : $ref->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

            // Loads property "@CascadeDelete" & "@CascadePersist" definitions if nothing has been found yet.
            foreach ($ref_properties as $property) {
                // "@CascadePersist"
                if (true == is_null($this->annotations[$class]['cascade_persist']['properties'][$name = $property->getName()] ?? null)) {
                    $prop_persist_anno = $this->reader->getPropertyAnnotation($property, CascadePersist::class);

                    if (false == is_null($prop_persist_anno)) {
                        $this->annotations[$class]['cascade_persist']['properties'] =
                            $this->annotations[$class]['cascade_persist']['properties'] ?? [];
                        $this->annotations[$class]['cascade_persist']['properties'][$name] = $prop_persist_anno->cascade();
                    }
                }

                // "@CascadeDelete"
                if (true == is_null($this->annotations[$class]['cascade_deletes'][$name] ?? null)) {
                    $prop_delete_anno = $this->reader->getPropertyAnnotation($property, CascadeDelete::class);
                    if (false == is_null($prop_delete_anno)) {
                        $prop_del_anno_loaded[] = $name;

                        // Property name is used as "rule name".
                        // It can override "@CascadeDeletes['list']['name']" definition.
                        $cascade_deletes_list[$name] = [
                            'class' => $sub_class = $prop_delete_anno->class
                        ];

                        $this->loadAnnotations($sub_class);
                    }
                }
            }

            // Merge all "cascade delete" definitions.
            $this->annotations[$class]['cascade_deletes'] = array_merge(
                $cascade_deletes_list,
                $this->annotations[$class]['cascade_deletes']
            );

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

        $structure = $this
            ->getSession()
            ->getRepository($class)
            ->getStructure();

        if (false == empty($diff = array_diff($prop_del_anno_loaded, $fields = $structure->getFieldNames()))) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to define "cascade deletes" definition.'.PHP_EOL
                    .'"%s::$%s" property can\'t be associated to a "@CascadeDelete" annotation.'.PHP_EOL
                    .'Only {"%s"} properties, that are part of structure definition, can.',
                    $class,
                    current($diff),
                    join('", "', $fields)
                )
            );
        }

        return $this->annotations[$class];
    }

    /**
     * Flush entities (insert, update, delete).
     * Cascade "flush" on related entities by default.
     *
     * @param  AbstractEntity[]|AbstractEntity|null $entities
     * @return self
     */
    public function flush($entities = null, bool $cascade_persist = null, bool $cascade_delete = null): self
    {
        $indexed_entities = $entities_previous_fk = $indexed_deferred =
        $deleted = $entities_status = [];
        $cascade_delete = $cascade_delete ?? true;

        // 1st step : Defines entities to flush
        if (false == is_null($entities)) {
            $entities = $this->travel($entities, false, $cascade_persist);
        } else {
            $entities = $this->getPersisted();
        }

        // 2nd step : Order entities - Dispatch each entity into its class group
        foreach ($entities as $hash => $entity) {
            $layer      = $this->getLayer($class = get_class($entity));
            $repository = $layer->getRepository();

            // Updates entity status denpending on data changes
            $repository->setEntityStatusAuto($entity);

            // Prepares cascade delete
            if (true == $cascade_delete && true == $entity->isStatus($entity::STATUS_TO_DELETE)) {
                $deleted[$class]        = $deleted[$class] ?? [];
                $deleted[$class][$hash] = $entity;
            } elseif (false == empty($foreign_key = $repository->getStructure()->getForeignKey())) {
                // Prevents SQL constraint errors during insert and update statement with DEFERRABLE SQL mode

                // Retrieves foreigns keys
                $entity_foreign_key = $entity->extract(array_keys($foreign_key));
                $required           = $repository->getStructure()->getNotNull();
                // Replaces foreign keys "null" values by a temporary unique identifier,
                // to avoid "not null" SQL constraint error
                // Final values will be updated later (see "5th step")
                foreach ($entity_foreign_key as $name => $value) {
                    // if "null" && "required"
                    if (true == is_null($value)
                        && true == ($required[$name] ?? null)
                    ) {
                        $tmp_id                    = ($tmp_id ??  time()) + 1;
                        $entity_foreign_key[$name] = (-1)*$tmp_id;

                        $entity->set($name, $entity_foreign_key[$name]);
                    }
                }

                // Store foreigns keys (will be used to checks changes later)
                $entities_previous_fk[$hash] = $entity_foreign_key;
            }

            // Index entity
            $indexed_entities[$class]   = $indexed_entities[$class] ?? [];
            $indexed_entities[$class][] = $entity;
        }

        // 3rd step : Start default transaction if it doesn't exists yet
        $transaction_id = $transaction_id ?? $this->initDefaultTransaction();

        // 4th step : Actives DEFERRABLE SQL mode.
        // Required to manage easly (via multiple statements) some specials cases,
        // like new entity pointed through foreign key
        $this->setDefaultDeferrable([], Connection::CONSTRAINTS_DEFERRED);

        // 5th step : Flush entities grouped by class.
        foreach ($indexed_entities as $class => $entities_group) {
            // Proceeds to insert/update/delete statements
             $this->getLayer($class)->flush($entities_group);
        }

        // 6th step : Define entities to update with new foreign key.
        // Inserted entities have now (after 5th step) primary key
        // that can be used as foreign key by related entities.
        foreach ($entities_previous_fk as $hash => $fks) {
            $class = get_class($entities[$hash]);

            // Retrieves synchronized data
            // If mapping is well defined, auto generated primary keys are now part of datas
            // Thanks to "MapPropertiesManager" synchronization
            $entity_fk = $entities[$hash]->extract(array_keys($fks));

            foreach ($fks as $name => $value) {
                // Checks if foreigns keys have been changed
                if ((string) $value !== (string) $entity_fk[$name]) {
                    // Store entity & previous foreigns keys
                    $indexed_deferred[$class]   = $indexed_deferred[$class] ?? [];
                    $indexed_deferred[$class][] = $entities[$hash];

                    // Set status to allow update
                    $entities[$hash]->setStatus([AbstractEntity::STATUS_MODIFIED => true]);
                    break;
                }
            }
        }

        // 7th step : Flush entities to update
        foreach ($indexed_deferred as $class => $entities_group) {
            $this->getLayer($class)->flush($entities_group);
        }

        // 8th step : Delete cascade.
        if (false == empty($deleted)) {
            $this->deleteCascade($deleted);
        }

        // 9th step : Commit transaction
        if (true == isset($transaction_id)) {
            $this->commitDefaultTransaction($transaction_id);
        }

        return $this;
    }

    /**
     * Deletes entities defined as "to cascade".
     *
     * @param  array $grouped_entities
     * @return self
     */
    private function deleteCascade(array $grouped_entities): self
    {
        // Retrieves cascade deletes mapping definition
        $mapping = [];

        do {
            $class = key($grouped_entities);

            $mapping = array_merge_recursive(
                $mapping,
                $this->getDeleteCascadeMapping($class)
            );
        } while (next($grouped_entities));

        $history = [];

        do {
            $conditions = [];
            $new_data   = false;

            // For each class to cascade
            foreach ($mapping as $class_cascaded => $columns) {
                // Defines conditions (colmuns & values).
                foreach ($columns as $column => $column_conditions) {
                    $values = [];

                    foreach ($column_conditions as $class_concerned => $fields) {
                        foreach ($grouped_entities[$class_concerned] ?? [] as $entity) {
                            // Avoids to make several times the same query.
                            $condition_identifier = $class_cascaded.'||'.$column.'||'.$class_concerned;
                            $history[$condition_identifier] = $history[$condition_identifier] ?? [];

                            if (true == in_array($entity->getHash(), $history[$condition_identifier])) {
                                continue;
                            }

                            $history[$condition_identifier][] = $entity->getHash();

                            $values = array_merge(
                                $values,
                                array_filter(
                                    array_values($entity->extract($fields)),
                                    function ($val) {
                                        return false == is_null($val);
                                    }
                                )
                            );
                        }
                    }

                    if (false == empty($values)) {
                        $conditions[$class_cascaded][$column] = array_values(array_unique($values));
                    }
                }

                // If conditions not defined
                if (false == isset($conditions[$class_cascaded])) {
                    continue;
                }

                $repository = $this
                    ->getSession()
                    ->getRepository($class_cascaded);

                $deleted = $repository->deleteGrouped($conditions[$class_cascaded]);

                foreach ($deleted as $hash => $entity) {
                    if (false == isset($grouped_entities[$class = get_class($entity)][$hash])) {
                        $grouped_entities[$class][$hash] = $entity;
                        $new_data = true;
                    }
                }
            }
        } while (true == $new_data);

        return $this;
    }

    /**
     * Returns cascade deletes mapping definition.
     *
     * @param  string $class
     * @return array
     */
    private function getDeleteCascadeMapping(string $class): array
    {
        if (true == array_key_exists($class, $this->deletes_mapping)) {
            return $this->deletes_mapping[$class];
        }

        $mapping       = [];
        $class_to_load = [$class => $class];

        do {
            $annotations       = $this->getAnnotations($class_loaded = key($class_to_load));
            $class_loaded_repo = $this
                ->getSession()
                ->getRepository($class_loaded);

            foreach ($annotations['cascade_deletes'] as $property => $annotation) {
                if (true == is_null($foreign_class = $annotation['class'] ?? null)) {
                    continue;
                }

                $class_to_load[$foreign_class] = $foreign_class;

                $foreign_repository = $this
                    ->getSession()
                    ->getRepository($foreign_class);

                if (true == is_null($foreign_prop = $annotation['map'] ?? null)) {
                    if (count($primary_key = $foreign_repository->getStructure()->getPrimaryKey()) > 1) {
                        $this->exception_manager->throw(
                            self::class,
                            __LINE__,
                            sprintf(
                                'Failed to cascade delete.'.PHP_EOL
                                .'"%s" class is cascaded and its primary key is composed of several columns.'.PHP_EOL
                                .'This case is not manage yet.',
                                $foreign_class
                            )
                        );
                    }

                    $foreign_prop = current($primary_key);
                } else {
                    if (count($class_loaded_pk = $class_loaded_repo->getStructure()->getPrimaryKey()) > 1) {
                        $this->exception_manager->throw(
                            self::class,
                            __LINE__,
                            sprintf(
                                'Failed to cascade delete.'.PHP_EOL
                                .'"%s" class is cascaded and depends on "%s" primary key.'.PHP_EOL
                                .'That primary key is composed of several columns ({"%s"}).'.PHP_EOL
                                .'This case is not manage yet.',
                                $foreign_class,
                                $class_loaded,
                                join('", "', $class_loaded_pk)
                            )
                        );
                    }

                    if (false == in_array($foreign_prop, $foreign_repository->getStructure()->getFieldNames())) {
                        $this->exception_manager->throw(
                            self::class,
                            __LINE__,
                            sprintf(
                                'Failed to cascade delete.'.PHP_EOL
                                .'Invalid property definition.'.PHP_EOL
                                .'"%s" class defines "%s" as property to use for cascade delete "%s" class.'.PHP_EOL
                                .'"%s::$%s" property doesn\'t exists.',
                                $class_loaded,
                                $foreign_prop,
                                $foreign_class,
                                $foreign_class,
                                $foreign_prop
                            )
                        );
                    }

                    $property = current($class_loaded_pk);
                }

                $mapping[$foreign_class][$foreign_prop][$class_loaded]
                    = $mapping[$foreign_class][$foreign_prop][$class_loaded] ?? [];
                $mapping[$foreign_class][$foreign_prop][$class_loaded][] = $property;
            }

            $this->deletes_mapping[$class] = $mapping;
        } while (next($class_to_load));

        return $this->deletes_mapping[$class];
    }

    /**
     * Returns entity layer.
     *
     * @param  string $entity_class
     * @return AbstractLayer
     */
    private function getLayer(string $entity_class): AbstractLayer
    {
        if (false == isset($this->layers[$entity_class])) {
            $repository = $this
                ->getSession()
                ->getClientUsingPooler('repository', $entity_class);

            $this->layers[$entity_class] = $repository->getLayer();
        }

        return $this->layers[$entity_class];
    }

    /**
     * Generates entity persisted identifier.
     *
     * @param  AbstractEntity $entity
     * @return string
     */
    private function generatePersistedId(AbstractEntity $entity): string
    {
        return $entity->getSandbox(static::class)['persisted_id']
        ?? ($entity->getSandbox(static::class)['persisted_id'] = $entity->getHash());
    }

    /**
     * Returns entity persisted identifier.
     *
     * @param  AbstractEntity $entity
     * @return string|null
     */
    private function getPersistedId(AbstractEntity $entity): ?string
    {
        return $entity->getSandbox(static::class)['persisted_id'] ?? null;
    }
}
