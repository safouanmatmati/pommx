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

namespace PommX\Relation;

use PommProject\Foundation\Pomm;

use Doctrine\Common\Annotations\Reader;

use PommX\Entity\AbstractEntity;
use PommX\Relation\RelationTrait;
use PommX\Relation\Annotation\Relation;

use PommX\Tools\Exception\ExceptionManagerInterface;
use PommX\Tools\InheritedReflectionClass;
use PommX\Tools\CheckIntegrityTrait;
use PommX\Tools\UnawareVisibilityTrait;

class RelationsManager
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
     * [private description]
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    /**
     * [private description]
     *
     * @var array
     */
    private $relations = [];

    /**
     * [private description]
     *
     * @var array
     */
    private $relations_names = [];

    /**
     * [private description]
     *
     * @var ReflectionProperty[]
     */
    private $reflections = [];

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
    private $annotations;

    public function __construct(Pomm $pomm, ExceptionManagerInterface $exception_manager, Reader $reader)
    {
        $this->pomm              = $pomm;
        $this->exception_manager = $exception_manager;
        $this->reader            = $reader;
    }

    /**
     * [initialize description]
     */
    public function initialize(AbstractEntity ...$entities)
    {
        foreach ($entities as $entity) {
            if (true == $this->isEntityInitialized($entity)) {
                continue;
            }

            $class = get_class($entity);

            $ref = new InheritedReflectionClass($class);

            if (false == $ref->hasTrait(RelationTrait::class)) {
                $this->throw(
                    __LINE__,
                    sprintf(
                        'Failed to initialize entity through "%s".'.PHP_EOL
                        .'"%s" class doesn\'t use "%s" trait as expected.',
                        self::class,
                        $class,
                        RelationTrait::class
                    )
                );
            }

            // Inject manager to be used inside "RelationTrait" trait
            $entity->relationGetSandbox()['manager'] = $this;

            $this->defineRelations($class);

            foreach (array_keys($this->getRelations($class)) as $relation_name) {
                $this->initProperties($class, $relation_name, $entity);
            }

            $this->syncAll($class, $entity);
        }
    }

    /**
     * Synchronizes all relations.
     *
     * @param  string         $class
     * @param  AbstractEntity $entity
     * @return self
     */
    public function syncAll(string $class, AbstractEntity $entity)
    {
        if (false == $this->isEntityInitialized($entity)) {
            return;
        }

        foreach ($this->getRelations($class) as $value) {
            $this->sync($class, $value['source'], $entity);
        }

        return $this;
    }

    /**
     * Synchornize specific relation.
     *
     * @param  string         $class
     * @param  string         $name
     * @param  AbstractEntity $entity
     * @param  array|null     $previous_values
     * @return self
     */
    public function sync(
        string $class,
        string $name,
        AbstractEntity $entity
    ): self {
        if (false == $this->isEntityInitialized($entity)) {
            return $this;
        }

        $relation_name = $this->getRealRelationName($class, $name);

        $this->initProperties($class, $relation_name, $entity);
        $relation_type = $this->getRelationType($class, $relation_name);

        if (true == in_array($relation_type, $this->getRelationTypeList('toMany'))
            && false == empty($collection = $this->getRelatedCollection($class, $relation_name, $entity))
        ) {
            $collection_indexed = [];
            $collection_name    = $this->getRelatedPropName($class, $relation_name);

            // Check related entity class && generate keys
            foreach ($collection as $related_entity) {
                self::checkRelatedEntityType(
                    $class,
                    $relation_name,
                    $related_entity,
                    __LINE__,
                    sprintf(
                        'Failed to re-index related entity from "%s::$%s"',
                        $class,
                        $collection_name
                    )
                );

                $key = $this->getIdentifier($related_entity);
                $collection_indexed[$key] = $related_entity;
            }

            foreach (array_diff_key($collection_indexed, $collection) as $new_entity) {
                $this->addRelated($class, $relation_name, $entity, $new_entity);
            }

            // Replaces by indexed ones
            $this->setProperty($class, $relation_name, $entity, $collection_name, $collection_indexed);
        } elseif (true == in_array($relation_type, $this->getRelationTypeList('toOne'))
            && false == empty($related_entity = $this->getSingleEntity($class, $relation_name, $entity))
        ) {
            // Sync new one
            self::checkRelatedEntityType(
                $class,
                $relation_name,
                $related_entity,
                __LINE__,
                'Failed to attach new entity'
            );

            // Calls setter to synchronize both side
            $this->setSingleEntity($class, $relation_name, $entity, null);
            $this->setSingleEntity($class, $relation_name, $entity, $related_entity);
        }

        return $this;
    }

    /**
     * [getRelations description]
     *
     * @param  string $class, string $relation_name
     * @return array
     */
    public function getMidRelations(string $class, string $relation_name, AbstractEntity $entity): array
    {
        $relation_name = $this->getRealRelationName($class, $relation_name);

        $this->initProperties($class, $relation_name, $entity);

        self::checkMidRelationDefinition($class, $relation_name);

        $collection = $this->getProperty(
            $class,
            $relation_name,
            $entity,
            $this->getMidPropName($class, $relation_name)
        );

        if (true == is_null($collection)) {
            $collection = [];
            $this->setProperty(
                $class,
                $relation_name,
                $entity,
                $this->getMidPropName($class, $relation_name),
                $collection
            );
        } elseif (false == is_array($collection)) {
            $this->throw(
                __LINE__,
                sprintf(
                    '"%s" is type of "%s", "array" expected',
                    $this->getMidPropName($class, $relation_name),
                    gettype($collection)
                ),
                $class,
                $relation_name
            );
        }

        return $collection;
    }

    /**
     * [addMidRelation description]
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  object         $mid_relation
     * @return self
     */
    public function addMidRelation(string $class, string $relation_name, AbstractEntity $entity, $mid_relation): self
    {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $this->initProperties($class, $relation_name, $entity);

        self::checkMidRelationDefinition($class, $relation_name);

        if (false == $this->hasMidRelation($class, $relation_name, $entity, $mid_relation)) {
            $key       = $this->getIdentifier($mid_relation);
            $tmp       = $this->getMidRelations($class, $relation_name, $entity);
            $tmp[$key] = $mid_relation;

            $this->setProperty(
                $class,
                $relation_name,
                $entity,
                $this->getMidPropName($class, $relation_name),
                $tmp
            );

            // Check that both entities exists in relation
            $this->getFromMidRelation($class, $relation_name, $entity, $mid_relation, 'current_entity');
            $this->getFromMidRelation($class, $relation_name, $entity, $mid_relation, 'related_entity');
        }

        return $this;
    }

    /**
     * [removeMidRelation description]
     *
     * @param  string         $class         [description]
     * @param  string         $relation_name [description]
     * @param  AbstractEntity $entity        [description]
     * @param  mixed          $relation      [description]
     * @return bool                          [description]
     */
    public function removeMidRelation(string $class, string $relation_name, AbstractEntity $entity, $relation): bool
    {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $this->initProperties($class, $relation_name, $entity);

        self::checkMidRelationDefinition($class, $relation_name);

        $mid_property  = $this->getMidPropName($class, $relation_name);
        $related_entity = $this->getFromMidRelation($class, $relation_name, $entity, $relation, 'related_entity');
        $key            = $this->getIdentifier($relation);
        $tmp            = $this->getProperty($class, $relation_name, $entity, $mid_property);

        unset($tmp[$key]);

        $this->setProperty($class, $relation_name, $entity, $mid_property, $tmp);
        $related_relation_name = $this->getRelatedRelationName($class, $relation_name);
        $related_class         = $this->getRelatedClass($class, $relation_name);

        if (true === $this->hasMidRelation($related_class, $related_relation_name, $related_entity, $relation)) {
            $this->removeMidRelation($related_class, $related_relation_name, $related_entity, $relation);
        }

        $this->setMidProperty(
            $class,
            $relation_name,
            $relation,
            $this->getMidCurrentPropName($class, $relation_name),
            null
        );

        return true;
    }

    /**
     * [hasMidRelation description]
     * @param  string         $class         [description]
     * @param  string         $relation_name [description]
     * @param  AbstractEntity $entity        [description]
     * @param  [type]         $relation      [description]
     * @return bool                          [description]
     */
    public function hasMidRelation(string $class, string $relation_name, AbstractEntity $entity, $relation): bool
    {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $this->initProperties($class, $relation_name, $entity);

        self::checkMidRelationDefinition($class, $relation_name);

        $key = $this->getIdentifier($relation);
        return array_key_exists($key, $this->getMidRelations($class, $relation_name, $entity));
    }

    /**
     * Returns middle relation shared by each side.
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  AbstractEntity $related_entity
     * @return object|null
     */
    public function getMidRelationWith(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        AbstractEntity $related_entity
    ) {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $this->initProperties($class, $relation_name, $entity);

        self::checkMidRelationDefinition($class, $relation_name);

        $related_relation_name = $this->getRelatedRelationName($class, $relation_name);
        $related_class         = $this->getRelatedClass($class, $relation_name);

        foreach ($this->getMidRelations($class, $relation_name, $entity) as $relation) {
            if (true == $this->hasMidRelation($related_class, $related_relation_name, $related_entity, $relation)) {
                return $relation;
            }
        }

        return null;
    }

    public function autoGet(AbstractEntity $entity, string $property)
    {
        if ($relation_name = $this->getRelationNameFromProperty($class = get_class($entity), $property)) {
            $this->initProperties($class, $relation_name, $entity);

            switch ($type = $this->getRelationType($class, $relation_name)) {
            case in_array($type, $this->getRelationTypeList('toOne')):
                return $this->getSingleEntity($class, $relation_name, $entity);
                    break;
            case in_array($type, $this->getRelationTypeList('toMany')):
                return $this->getRelatedCollection($class, $relation_name, $entity);
                    break;
            }
        } elseif ($relation_name = $this->getRelationNameFromMidProperty($class, $property)) {
            return $this->getMidRelations($class, $relation_name, $entity);
        }

        return null;
    }

    public function autoSet(AbstractEntity $entity, string $property, $value): ?bool
    {
        if ($relation_name = $this->getRelationNameFromProperty($class = get_class($entity), $property)) {
            $this->initProperties($class, $relation_name, $entity);

            switch ($type = $this->getRelationType($class, $relation_name)) {
            case in_array($type, $this->getRelationTypeList('toOne')):
                $this->setSingleEntity($class, $relation_name, $entity, $value);
                break;
            case in_array($type, $this->getRelationTypeList('toMany')):
                $this->setRelatedCollection($class, $relation_name, $entity, $value);
                break;
            }

            return true;
        }

        return null;
    }

    public function autoAdd(AbstractEntity $entity, string $property, $value): ?bool
    {
        if ($relation_name = $this->getRelationNameFromProperty($class = get_class($entity), $property)) {
            $this->initProperties($class, $relation_name, $entity);

            if (true == in_array($this->getRelationType($class, $relation_name), $this->getRelationTypeList('toMany'))) {
                return $this->addRelated($class, $relation_name, $entity, $value);
            }
        } elseif ($relation_name = $this->getRelationNameFromMidProperty($class, $property)) {
            return $this->addMidRelation($class, $relation_name, $entity, $value);
        }

        return null;
    }

    public function autoRemove(AbstractEntity $entity, string $property, $value): ?bool
    {
        if ($relation_name = $this->getRelationNameFromProperty($class = get_class($entity), $property)) {
            $this->initProperties($class, $relation_name, $entity);

            if (true == in_array($this->getRelationType($class, $relation_name), $this->getRelationTypeList('toMany'))) {
                return $this->removeRelated($class, $relation_name, $entity, $value);
            }
        } elseif ($relation_name = $this->getRelationNameFromMidProperty($class, $property)) {
            return $this->removeMidRelation($class, $relation_name, $entity, $value);
        }

        return null;
    }

    public function autoHas(AbstractEntity $entity, string $property, $value): ?bool
    {
        if ($relation_name = $this->getRelationNameFromProperty($class = get_class($entity), $property)) {
            $this->initProperties($class, $relation_name, $entity);

            if (true == in_array($this->getRelationType($class, $relation_name), $this->getRelationTypeList('toMany'))) {
                return $this->hasRelated($class, $relation_name, $entity, $value);
            }
        } elseif ($relation_name = $this->getRelationNameFromMidProperty($class, $property)) {
            return $this->hasMidRelation($class, $relation_name, $entity, $value);
        }

        return null;
    }

    /**
     * Checks whether a relation is contained in the collection.
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  AbstractEntity $related_entity
     * @return bool
     */
    public function hasRelated(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        AbstractEntity $related_entity
    ): bool {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $this->initProperties($class, $relation_name, $entity);

        self::checkRelatedEntityType(
            $class,
            $relation_name,
            $related_entity,
            __LINE__,
            sprintf(
                'Failed to test if related entity is contained into "%s::$%s".',
                $class,
                $this->getRelatedPropName($class, $relation_name)
            )
        );

        $key = $this->getIdentifier($related_entity);
        return array_key_exists($key, $this->getRelatedCollection($class, $relation_name, $entity));
    }

    /**
     * Add a related entity.
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  AbstractEntity $related_entity
     * @return bool
     */
    public function addRelated(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        AbstractEntity $related_entity
    ): bool {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $this->initProperties($class, $relation_name, $entity);
        $collection_name = $this->getRelatedPropName($class, $relation_name);

        self::checkRelatedEntityType(
            $class,
            $relation_name,
            $related_entity,
            __LINE__,
            sprintf(
                'Failed to add related entity into "%s::$%s".',
                $class,
                $collection_name
            )
        );

        if (true == $this->hasRelated($class, $relation_name, $entity, $related_entity)) {
            return true;
        }

        $tmp = $this->getProperty(
            $class,
            $relation_name,
            $entity,
            $this->getRelatedPropName($class, $relation_name)
        );
        $key       = $this->getIdentifier($related_entity);
        $tmp[$key] = $related_entity;
        $this->setProperty($class, $relation_name, $entity, $this->getRelatedPropName($class, $relation_name), $tmp);

        $type                  = $this->getRelationType($class, $relation_name);
        $related_relation_name = $this->getRelatedRelationName($class, $relation_name);
        $related_class         = $this->getRelatedClass($class, $relation_name);

        switch ($type) {
        case 'manyToMany':
            if (true == $this->midRelationIsDefined($class, $relation_name)) {
                if (true == is_null($mid_relation = $this->getMidRelationWith($related_class, $related_relation_name, $related_entity, $entity))) {
                    $params = [
                        ($mid_current_property = $this->getMidCurrentPropName($class, $relation_name)) => $entity,
                        ($mid_related_property = $this->getMidRelatedPropName($class, $relation_name)) => $related_entity
                    ];

                    $mid_relation = $this->getMidRelationInstance($class, $relation_name, $entity, $params);
                    $this->setMidProperty($class, $relation_name, $mid_relation, $mid_current_property, $entity);
                    $this->setMidProperty($class, $relation_name, $mid_relation, $mid_related_property, $related_entity);
                }

                $this->addMidRelation($class, $relation_name, $entity, $mid_relation);
                $this->addMidRelation($related_class, $related_relation_name, $related_entity, $mid_relation);
            }

            $this->addRelated($related_class, $related_relation_name, $related_entity, $entity);

            break;

        case 'oneToMany':
                $this->setSingleEntity($related_class, $related_relation_name, $related_entity, $entity);
            break;
        default:
                $this->throw(__LINE__, '"'.$type.'" as "type" isn\'t allowed here.', $class, $relation_name);
            break;
        }

        return true;
    }

    /**
     * Remove a related entity.
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  AbstractEntity $related_entity
     * @return bool
     */
    public function removeRelated(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        AbstractEntity $related_entity
    ): bool {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $this->initProperties($class, $relation_name, $entity);
        $collection_name = $this->getRelatedPropName($class, $relation_name);

        self::checkRelatedEntityType(
            $class,
            $relation_name,
            $related_entity,
            __LINE__,
            sprintf(
                'Failed to remove related entity from "%s::$%s".',
                $class,
                $collection_name
            )
        );

        if (false == $this->hasRelated($class, $relation_name, $entity, $related_entity)) {
            return true;
        }

        // Detachs $related_entity
        $key = $this->getIdentifier($related_entity);
        $tmp = $this->getProperty($class, $relation_name, $entity, $collection_name);
        unset($tmp[$key]);
        $this->setProperty($class, $relation_name, $entity, $collection_name, $tmp);

        $type = $this->getRelationType($class, $relation_name);
        $related_relation_name = $this->getRelatedRelationName($class, $relation_name);
        $related_class = $this->getRelatedClass($class, $relation_name);

        // Synchronizes other side
        switch ($type) {
        case 'manyToMany':
            if (true == $this->hasRelated($related_class, $related_relation_name, $related_entity, $entity)) {
                $this->removeRelated($related_class, $related_relation_name, $related_entity, $entity);
            }

            if (false == $this->midRelationIsDefined($class, $relation_name)) {
                break;
            }

            if (false == is_null($mid_relation = $this->getMidRelationWith($class, $relation_name, $entity, $related_entity))) {
                $this->removeMidRelation($class, $relation_name, $entity, $mid_relation);
            }
            break;

        case 'oneToMany':
            $this->setSingleEntity($related_class, $related_relation_name, $related_entity, null);
            break;
        default:
            $this->throw(__LINE__, '"'.$type.'" as "type" isn\'t allowed here.', $class, $relation_name);
            break;
        }

        return true;
    }

    /**
     * [getNewMidRelation description]
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  array          $parameters
     * @return [type]
     */
    private function getMidRelationInstance(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        array $parameters
    ) {
        $relation_name = $this->getRealRelationName($class, $relation_name);
        $callback    = $this->getMidFactory($class, $relation_name);

        try {
            $default_parameters = [
                'initiator'  => $entity,
                'parameters' => $parameters,
                'session'    => $this->pomm->getDefaultSession()
            ];

            // If arguments have been passed to "mid.factory" option, add them.
            if (true == is_array($callback)) {
                foreach ($callback as $key => $param) {
                    if (true == is_array($param)) {
                        $default_parameters = array_merge($default_parameters, $param);
                        unset($callback[$key]);
                    }
                }
            }

            $instance = call_user_func($callback, $default_parameters);
        } catch (\Exception $e) {
            $this->throw(
                __LINE__,
                sprintf(
                    'Failed to retrieve instance of mid relation, '
                    .'"mid.factory" callback failed.'
                ),
                $class,
                $relation_name,
                null,
                $e
            );
        }

        $mid_class = $this->getRelations($class, $relation_name)['mid.class'];

        if (false == is_object($instance) || false == is_a($instance, $mid_class)) {
            $this->throw(
                __LINE__,
                sprintf(
                    'Failed to retrieve instance of mid relation.'.PHP_EOL
                    .'"%s" class expected, "%s" found.',
                    $mid_class,
                    (is_object($instance) ? get_class($instance) : gettype($instance))
                ),
                $class,
                $relation_name
            );
        }

        return $instance;
    }

    /**
     * Initialize $entity properties.
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @return self
     */
    private function initProperties(string $class, string $relation_name, AbstractEntity $entity): self
    {
        // Reference returned
        $sandbox = &$entity->relationGetSandbox();

        $sandbox['initialized'] = $sandbox['initialized'] ?? [];

        if (true == isset($sandbox['initialized'][$relation_name])) {
            return $this;
        }

        $relation = $this->getRelations($class, $relation_name);

        if (true == in_array($relation['type'], $this->getRelationTypeList('toOne'))) {
            $name = $this->getRelatedPropName($class, $relation_name, $entity, false);
            $this->initProperty($class, $relation_name, $entity, $name, null);
        } elseif (true == in_array($relation['type'], $this->getRelationTypeList('toMany'))) {
            $name = $this->getRelatedPropName($class, $relation_name, $entity, false);
            $this->initProperty($class, $relation_name, $entity, $name, []);

            if ('manyToMany' == $relation['type'] && true == $this->midRelationIsDefined($class, $relation_name)) {
                $name = $this->getMidPropName($class, $relation_name, $entity, false);
                $this->initProperty($class, $relation_name, $entity, $name, []);
            }
        }

        $sandbox['initialized'][$relation_name] = true;

        return $this;
    }

    /**
     * [isEntityInitialized description]
     *
     * @return bool
     */
    private function isEntityInitialized(AbstractEntity $entity): bool
    {
        return isset($entity->relationGetSandbox()['initialized']);
    }

    /**
     * Checks config integrity and format it as a one dimentional array.
     *
     * @param  string $class
     * @param  string $source
     * @param  array  $relation
     * @return array
     */
    private function checkAndFormatConfig(string $class, string $source, array $relation): array
    {
        // Checks params definition validity
        $params_definitions = [
            'related'  => ['string', 'array'],
            // "property" can be NULL if "related.property" is defined.
            'property' => ['string', 'callable', 'NULL'],
            // "type" can be NULL if "related.class" is defined.
            'type'     => ['string', 'NULL'],
            'setter'   => ['array', 'callable', 'NULL'],
            'getter'   => ['array', 'callable', 'NULL'],
            'mid'      => ['array', 'NULL']
        ];

        // Checks params definition validity
        $sub_level_def = [
            'mid' => [
                'class'            => ['string', 'NULL'],
                'factory'          => ['array', 'NULL'],
                'property'         => ['string', 'NULL'],
                'current_property' => ['string', 'NULL'],
                'related_property' => ['string', 'NULL'],
                'setter'           => ['array', 'callable', 'NULL'],
                'getter'           => ['array', 'callable', 'NULL']
            ],
            'related'          => [
                'class'        => ['string'],
                'setter'       => ['array', 'callable', 'NULL'],
                'getter'       => ['array', 'callable', 'NULL'],
                'property'     => ['string', 'callable', 'NULL'],
                'mid_property' => ['string', 'NULL']
            ]
        ];

        if (true == is_string($relation['related'] ?? null)) {
            $relation['related.class'] = $relation['related'];
            unset($relation['related']);
        }

        if (true == isset($relation['property'])) {
            $relation['related.property'] = $relation['property'];
            unset($relation['property']);
        }

        //  Convert "dot" notation to array
        foreach ($sub_level_def as $level => $def) {
            foreach (array_keys($def) as $param) {
                if (true == isset($relation[$level.'.'.$param])) {
                    $relation[$level]         = $relation[$level] ?? [];
                    $relation[$level][$param] = $relation[$level.'.'.$param];

                    unset($relation[$level.'.'.$param]);
                }
            }
        }

        try {
            self::checkArrayAssocIntegrity(
                $relation,
                $params_definitions,
                $this->exception_manager
            );
        } catch (\Exception $e) {
            $this->throw(
                __LINE__,
                sprintf(
                    'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                    .'Invalid annotation parameter.',
                    $class,
                    $source
                ),
                null,
                $e
            );
        }

        $dependencies = [
            'mid' => [
                'class' => 'factory',
                'factory' => 'class'
            ]
        ];

        foreach ($sub_level_def as $key => $def) {
            if (true == is_array($relation[$key] ?? null)) {
                try {
                    self::checkArrayAssocIntegrity(
                        $relation[$key],
                        $def,
                        $this->exception_manager
                    );

                    if (true == isset($dependencies[$key])) {
                        self::checkArrayAssocDependencies(
                            $relation[$key],
                            $dependencies[$key],
                            $this->exception_manager
                        );
                    }
                } catch (\Exception $e) {
                    $this->exception_manager->throw(
                        self::class,
                        __LINE__,
                        sprintf(
                            'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                            .'Invalid @Relation annotation parameter.'.PHP_EOL
                            .'"%s" array failed to be validate.',
                            $class,
                            $source,
                            $key
                        ),
                        null,
                        $e
                    );
                }

                //  Convert array to "dot" notation
                foreach (array_keys($relation[$key]) as $name) {
                    if (false == is_null($relation[$key][$name] ?? null)) {
                        $relation[$key.'.'.$name] = $relation[$key][$name];
                    }
                }
                unset($relation[$key]);
            }
        }

        if (true == is_callable($relation['related.property'])) {
            $relation['related.property'] = call_user_func($relation['related.property']);

            try {
                self::checkIntegrity(
                    'property, related.property',
                    $relation['related.property'] ?? null,
                    ['string'],
                    $this->exception_manager
                );
            } catch (\Exception $e) {
                $this->exception_manager->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                        .'Invalid annotation parameter.',
                        $class,
                        $source
                    ),
                    null,
                    $e
                );
            }
        }

        return $relation;
    }

    /**
     * Initialize relation
     *
     * @param  string      $class
     * @param  string      $source
     * @param  array       $relation
     * @param  string|null $related_relation_name
     * @return string
     */
    private function initRelation(
        string $class,
        string $source,
        array $relation,
        string $related_relation_name = null
    ): string {
        // Config already initialized, skip
        if (false == is_null($relation_name = $this->relations_names[$class][$source] ?? null)) {
            return $relation_name;
        }

        // Checks and format config as an one dimentional array
        $relation = $this->checkAndFormatConfig($class, $source, $relation);

        $related_config = [];

        // Try to merge current config with related config to have full config from both side.
        if (true == is_null($related_relation_name) && $class !== $relation['related.class']) {
            if (false == class_exists($relation['related.class'])) {
                $this->throw(
                    __LINE__,
                    sprintf(
                        'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                        .'Related class "%s" doesn\'t exists.',
                        $class,
                        $source,
                        $relation['related.class']
                    )
                );
            }

            $related_relations = $this->getAnnotations($relation['related.class']);

            // Find the corresponding one
            foreach ($related_relations as $related_source => $related_conf) {
                // classes must correspond
                if ($class !== ($related_conf['related']['class'] ?? $related_conf['related'] ?? null)) {
                    continue;
                }
                $property = ($related_conf['related']['property'] ?? $related_conf['property'] ?? null);

                if (true == is_callable($property)) {
                    $property = call_user_func($property);
                }

                // properties name must correspond
                if ($relation['related.property'] !== $related_source) {
                    continue;
                }

                if ($source !== $property) {
                    $this->throw(
                        __LINE__,
                        sprintf(
                            'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                            .'Configuration can\'t be completed from "%s::$%s" as expected.'.PHP_EOL
                            .'"%s::$%s" exptected to be related to "%s", "%s" found.',
                            $class,
                            $source,
                            $relation['related.class'],
                            $relation['related.property'],
                            $relation['related.class'],
                            $relation['related.property'],
                            $source,
                            $property
                        )
                    );
                }

                $related_config = $this->checkAndFormatConfig(
                    $relation['related.class'],
                    $related_source,
                    $related_conf
                );

                $relation = $this->mergeConfigBasedOn(
                    $class,
                    $source,
                    $relation,
                    $related_source,
                    $related_config
                );

                break;
            }
        }

        // 'type' can be retrieve from related conf, check it now.
        try {
            self::checkIntegrity(
                'type',
                $relation['type'] ?? null,
                ['string'],
                $this->exception_manager
            );
        } catch (\Exception $e) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                    .'Invalid annotation parameter.',
                    $class,
                    $source
                ),
                null,
                $e
            );
        }

        foreach ($this->getRelations($class) as $existing_config) {
            if ($source == $existing_config['source']) {
                $this->throw(
                    __LINE__,
                    sprintf(
                        'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                        .'"%s::$%s" is already related to "%s::$%s".'.PHP_EOL,
                        $class,
                        $source,
                        $class,
                        $source,
                        $existing_config['related.class'],
                        $existing_config['related.property']
                    )
                );
            }
        }

        $ref_current       = new InheritedReflectionClass($class);
        $current_property = $this->getSnakeCase($ref_current->getShortName());

        if ('manyToMany' === $relation['type']) {
            $current_property = $this->getPlurial($current_property);

            if (false == is_null($relation['mid.factory'] ?? null)) {
                $ref_related = new InheritedReflectionClass($relation['related.class']);
                $ref_current = new InheritedReflectionClass($class);

                $related_short_class_name = $this->getSnakeCase($ref_related->getShortName());
                $current_short_class_name = $this->getSnakeCase($ref_current->getShortName());

                $name_parts = [$current_short_class_name, $related_short_class_name];
                sort($name_parts);
                $relation['mid.property'] = $relation['mid.property'] ?? implode('_', $name_parts).'_relations';

                $relation['mid.current_property'] = $relation['mid.current_property'] ?? $current_short_class_name;
                $relation['mid.related_property'] = $relation['mid.related_property'] ?? $related_short_class_name;
            }
        } elseif ('manyToOne' === $relation['type']) {
            $current_property = $this->getPlurial($current_property);
        }

        $relation['related.property'] = $relation['related.property'] ?? $current_property;

        $relation_name = $relation['type'].sprintf('__%s__%s__', $relation['related.property'], $source);

        $copy_config = $relation;

        // Create & init related conf if needed,
        // except if its a "self manyToMany" relation or that is already done.
        if (false == empty($related_relation_name)) {
            $relation['related.config'] = $related_relation_name;
        } elseif ($relation['related.class'] == $class
            && true == in_array($relation['type'], ['manyToMany', 'oneToOne'])
        ) {
            $relation['related.config'] = $relation_name;
        } else {
            $related_class        = $copy_config['related.class'];
            $switched             = $this->switchPropertiesConfig($copy_config, $class);
            $related_config       = $switched + $related_config;

            $related_config['related.property'] = $source;
            $related_relation_name = $this->initRelation(
                $related_class,
                $relation['related.property'],
                $related_config,
                $relation_name
            );
            $relation['related.config'] = $related_relation_name;
        }

        $relation['source'] = $source;

        // Removes uncessary configuration property
        if (true == is_null($relation['mid.factory'] ?? null)) {
            foreach ($this->getMidParamsList() as $param) {
                unset($relation[$param]);
            }
        }

        ksort($relation);

        $this->addRelation($class, $relation_name, $relation);

        return $relation_name;
    }

    private function getRelationNameFromProperty(string $class, string $property): ?string
    {
        if (true == isset($this->relations_names[$class][$property])) {
            return $this->relations_names[$class][$property];
        }

        return null;
    }

    private function getRelationNameFromMidProperty(string $class, string $property): ?string
    {
        foreach ($this->getRelations($class) as $name => $relation) {
            if ($property == ($relation['mid.property'] ?? null)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * [getSingleEntity description]
     *
     * @param  string $class, string $relation_name
     * @return object
     */
    private function getSingleEntity(string $class, string $relation_name, AbstractEntity $entity)
    {
        return $this->getProperty($class, $relation_name, $entity, $this->getRelatedPropName($class, $relation_name));
    }

    /**
     * [setSingleEntity description]
     *
     * @param string      $relation_name
     * @param object|null $single_entity
     */
    private function setSingleEntity(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        ?AbstractEntity $single_entity
    ): self {
        $current = $this->getSingleEntity($class, $relation_name, $entity);

        // Prevents circular calls
        if ($current === $single_entity) {
            return $this;
        }

        $single_entity_name = $this->getRelatedPropName($class, $relation_name);

        if (false == is_null($single_entity)) {
            self::checkRelatedEntityType(
                $class,
                $relation_name,
                $single_entity,
                __LINE__,
                sprintf(
                    'Failed to set single entity "%s::$%s".',
                    $class,
                    $single_entity_name
                )
            );
        }

        $related_relation_name = $this->getRelatedRelationName($class, $relation_name);
        $related_class       = $this->getRelatedClass($class, $relation_name);
        $type                = $this->getRelationType($class, $relation_name);

        switch ($type) {
        case 'oneToOne':
            // Set single entity
            $this->setProperty($class, $relation_name, $entity, $single_entity_name, $single_entity);

            // Synchronize previous entity, detach
            if (false == is_null($current)) {
                $this->setSingleEntity($related_class, $related_relation_name, $current, null);
            }

            // Synchornize new entity
            if (false == is_null($single_entity)) {
                $this->setSingleEntity($related_class, $related_relation_name, $single_entity, $entity);
            }
            break;
        case 'manyToOne':
            // Synchornize previous entity
            if (false == is_null($current)) {
                $this->removeRelated($related_class, $related_relation_name, $current, $entity);
            }

            // Set single entity
            $this->setProperty($class, $relation_name, $entity, $single_entity_name, $single_entity);

            // Synchornize new entity
            if (false == is_null($single_entity)) {
                $this->addRelated($related_class, $related_relation_name, $single_entity, $entity);
            }
            break;
        default:
            $this->throw(__LINE__, '"'.$type.'" as "type" isn\'t allowed here.', $class, $relation_name);
            break;
        }

        return $this;
    }

    /**
     * [getRelatedCollection description]
     *
     * @param  string $class, string $relation_name
     * @return array|null
     */
    private function getRelatedCollection(string $class, string $relation_name, AbstractEntity $entity): ?array
    {
        $collection = $this->getProperty(
            $class,
            $relation_name,
            $entity,
            $this->getRelatedPropName($class, $relation_name)
        );

        if (true == is_null($collection)) {
            $collection = [];
            $this->setProperty(
                $class,
                $relation_name,
                $entity,
                $this->getRelatedPropName($class, $relation_name),
                $collection
            );
        } elseif (false == is_array($collection)) {
            $this->throw(
                __LINE__,
                sprintf(
                    '"%s" is type of "%s", "array" expected',
                    $this->getRelatedPropName($class, $relation_name),
                    gettype($collection)
                ),
                $class,
                $relation_name
            );
        }

        return $collection;
    }

    /**
     * Replaces all related entity collection.
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  array          $collection
     * @return self
     */
    private function setRelatedCollection(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        array $collection
    ): self {
        // Remove related entities
        array_map(
            function ($related_entity) use ($class, $relation_name, $entity) {
                $this->removeRelated($class, $relation_name, $entity, $related_entity);
            },
            $this->getRelatedCollection($class, $relation_name, $entity)
        );

        // Remove relations
        if ('manyToMany' == $this->getRelationType($class, $relation_name)
            && true == $this->midRelationIsDefined($class, $relation_name)
        ) {
            $this->setProperty($class, $relation_name, $entity, $this->getMidPropName($class, $relation_name), []);
        }

        // Add related entities
        array_map(
            function ($related_entity) use ($class, $relation_name, $entity) {
                $this->addRelated($class, $relation_name, $entity, $related_entity);
            },
            $collection
        );

        return $this;
    }

    /**
     * [getProperty description]
     *
     * @param  string         $class         [description]
     * @param  string         $relation_name [description]
     * @param  AbstractEntity $entity        [description]
     * @param  string         $property      [description]
     * @return [type]                        [description]
     */
    private function getProperty(string $class, string $relation_name, AbstractEntity $entity, string $property)
    {
        $relation = $this->getRelations($class, $relation_name);

        if (true == array_key_exists('getter', $relation)) {
            return call_user_func($relation['getter'], $entity, $property);
        }

        return $this->getUnawareVisibility($entity, $property);
    }

    /**
     * [setProperty]
     *
     * @param string      $relation_name
     * @param string      $property
     * @param mixed       $value
     * @param object|null $element
     */
    private function setProperty(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        string $property,
        $value
    ): self {
        $relation = $this->getRelations($class, $relation_name);

        if (true == array_key_exists('setter', $relation)) {
            call_user_func($relation['setter'], $entity, $property, $value);
            return $this;
        }

        $this->setUnawareVisibility($entity, $property, $value);

        return $this;
    }

    /**
     * [initProperty description]
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  AbstractEntity $entity
     * @param  string         $property
     * @param  mixed          $value
     * @return self
     */
    private function initProperty(
        string $class,
        string $relation_name,
        AbstractEntity $entity,
        string $property,
        $value
    ): self {
        if (false == property_exists($entity, $property)) {
            $this->setProperty($class, $relation_name, $entity, $property, $value);
        }

        return $this;
    }

    /**
     * [getMidProperty description]
     *
     * @param  string $class
     * @param  string $relation_name
     * @param  object $mid_relation
     * @param  string $property
     * @return mixed
     */
    private function getMidProperty(string $class, string $relation_name, $mid_relation, string $property)
    {
        $relation = $this->getRelations($class, $relation_name);

        if (true == array_key_exists('mid.getter', $relation)) {
            return call_user_func($relation['mid.getter'], $mid_relation, $property);
        }

        return $this->getUnawareVisibility($mid_relation, $property);
    }

    /**
     * [setMidProperty description]
     *
     * @param  string $class
     * @param  string $relation_name
     * @param  object $mid_relation
     * @param  string $property
     * @param  mixed  $value
     * @return self
     */
    private function setMidProperty(string $class, string $relation_name, $mid_relation, string $property, $value): self
    {
        $relation = $this->getRelations($class, $relation_name);

        if (true == array_key_exists('mid.setter', $relation)) {
            call_user_func($relation['mid.setter'], $mid_relation, $property, $value);
        } else {
            $this->setUnawareVisibility($mid_relation, $property, $value);
        }

        return $this;
    }

    /**
     * [getFromMidRelation description]
     *
     * @param  string $class,       string $relation_name
     * @param  object $mid_relation
     * @param  string $target
     * @return object|null
     */
    private function getFromMidRelation(
        string $class,
        string $relation_name,
        AbstractEntity $current,
        $mid_relation,
        string $target
    ) {
        $targets = ['current_entity', 'related_entity'];

        if (false == in_array($target, $targets)) {
            $this->throw(
                __LINE__,
                sprint(
                    '"%s" isn\'t allowed, use only one of "%s"',
                    $target,
                    implode("', '", $targets)
                ),
                $class,
                $relation_name
            );
        }

        $relation_name = $this->getRealRelationName($class, $relation_name);

        switch ($target) {
        case 'related_entity':
            $entity = $this->getMidProperty(
                $class,
                $relation_name,
                $mid_relation,
                $this->getMidRelatedPropName($class, $relation_name)
            );

            $relation = $this->getRelations($class, $relation_name);

            if (false == is_a($entity, $relation['related.class'])) {
                $this->throw(
                    __LINE__,
                    sprintf(
                        'Middle relation hasn\'t a valid "%s" related entity. '.PHP_EOL
                        .'"%s" type found, "%s" class expected.',
                        $this->getMidRelatedPropName($class, $relation_name),
                        (is_object($entity) ? get_class($entity) : gettype($entity)),
                        $relation['related.class']
                    ),
                    $class,
                    $relation_name
                );
            }

            break;

        case 'current_entity':
            if (false == $this->hasMidRelation($class, $relation_name, $current, $mid_relation)) {
                $this->throw(
                    __LINE__,
                    "Relation not founded."
                );
            }

            $entity = $this->getMidProperty(
                $class,
                $relation_name,
                $mid_relation,
                $this->getMidCurrentPropName($class, $relation_name)
            );

            // Check integrity
            if ($current !== $entity) {
                $this->throw(
                    __LINE__,
                    sprintf(
                        'Related entity "%s::$%s::$%s" isn\'t the one expected.'.PHP_EOL
                        .'"%s" type with hash "%s" founded.'.PHP_EOL
                        .'"%s" type with hash "%s" expected.',
                        $class,
                        $this->getMidPropName($class, $relation_name),
                        $this->getMidCurrentPropName($class, $relation_name),
                        (is_null($entity) ? 'NULL' : get_class($entity)),
                        (is_null($entity) ? 'NULL' : md5(spl_object_hash($entity))),
                        $class,
                        md5(spl_object_hash($entity))
                    ),
                    $class,
                    $relation_name
                );
            }
            break;
        }

        return $entity;
    }

    /**
     * [getMidCurrentPropName description]
     *
     * @param  string $class
     * @param  string $relation_name
     * @return string
     */
    private function getMidCurrentPropName(string $class, string $relation_name): string
    {
        return $this->getRelations($class, $relation_name)['mid.current_property'];
    }

    /**
     * [getMidRelatedPropName description]
     *
     * @param  string $class
     * @param  string $relation_name
     * @return string
     */
    private function getMidRelatedPropName(string $class, string $relation_name): string
    {
        return $this->getRelations($class, $relation_name)['mid.related_property'];
    }

    /**
     * [getMidPropName description]
     *
     * @param  string $class
     * @param  string $relation_name
     * @return string
     */
    private function getMidPropName(string $class, string $relation_name): string
    {
        return $this->getRelations($class, $relation_name)['mid.property'];
    }

    /**
     * [getMidFactory description]
     *
     * @param  string $class
     * @param  string $relation_name
     * @return [type]
     */
    private function getMidFactory(string $class, string $relation_name)
    {
        return $this->getRelations($class, $relation_name)['mid.factory'];
    }

    /**
     * [getRelatedPropName description]
     *
     * @param  string $class
     * @param  string $relation_name
     * @return string
     */
    private function getRelatedPropName(string $class, string $relation_name): string
    {
        return $this->getRelations($class, $relation_name)['source'];
    }

    /**
     * Return an unique object identifier.
     *
     * @param  object $element
     * @return string
     */
    private function getIdentifier($element): string
    {
        $ref = new InheritedReflectionClass(get_class($element));

        // Store "id" as object member property if "RelationTrait" trait  isn't used.
        if (false == $ref->hasTrait(RelationTrait::class)) {
            $element->trait_relation_entities_id = $element->trait_relation_entities_id
            ?? md5(spl_object_hash($element).rand());

            return $element->trait_relation_entities_id;
        }

        $sandbox = &$element->relationGetSandbox();
        $sandbox['id'] = $sandbox['id'] ?? md5(spl_object_hash($element).rand());

        return $sandbox['id'];
    }

    /**
     * Returns the real relation name, used by internal process,
     * from an alias provide from external source.
     *
     * @param  string     $class
     * @param  string     $alias
     * @param  array|null $types
     * @return string
     */
    private function getRealRelationName(
        string $class,
        string $alias
    ): string {
        // It's not an alias, return it
        if (true == array_key_exists($alias, $this->getRelations($class))) {
            return $alias;
        }

        if (false == is_null($name = $this->relations_names[$class][$alias] ?? null)) {
            return $name;
        }

        $this->throw(
            __LINE__,
            sprintf(
                '"%s::$%s" relation not found.'.PHP_EOL
                .'Is relation initialized ?',
                $class,
                $alias
            )
        );
    }

    /**
     * List of relation type.
     *
     * @param  [type] $type
     * @return array
     */
    private function getRelationTypeList(string $type = null): array
    {
        switch ($type) {
        case null:
            return ['oneToMany', 'manyToOne', 'oneToOne', 'manyToMany'];
        break;
        case 'toOne':
            return ['manyToOne', 'oneToOne'];
        break;
        case 'oneTo':
            return ['oneToMany', 'oneToOne'];
        break;
        case 'manyTo':
            return ['manyToOne', 'manyToMany'];
        break;
        case 'toMany':
            return ['oneToMany', 'manyToMany'];
        break;
        case 'mapped':
            return ['oneToMany' => 'manyToOne', 'manyToOne' => 'oneToMany'];
        break;
        default:
            $this->throw(
                __LINE__,
                sprintf(
                    '"%s" isn\'t a valid type.',
                    $type
                )
            );
            break;
        }
    }

    /**
     * [setConfigs description]
     *
     * @param string $class
     */
    private function defineRelations(string $class)
    {
        if (true == $this->isAllRelationsInitialized($class)) {
            return;
        }

        foreach ($this->getAnnotations($class) as $property => $relation) {
            $this->initRelation($class, $property, $relation);
        }

        $this->allRelationsInitialized($class, true);
    }

    /**
     * [getAnnotations description]
     *
     * @param  string $class
     * @return array
     */
    private function getAnnotations(string $class): array
    {
        if (false == is_null($this->annotations[$class] ?? null)) {
            return $this->annotations[$class];
        }

        $ref            = new InheritedReflectionClass($class);
        $ref_properties = $ref->getProperties();

        $relations = [];
        foreach ($ref_properties as $property) {
            if ($relation = $this->reader->getPropertyAnnotation($property, Relation::class)) {
                $relation = array_filter((array) $relation);
                $relations[$property->getName()] = $relation;
            }
        }

        return $this->annotations[$class] = $relations;
    }

    /**
     * [isAllRelationsInitialized description]
     *
     * @param  string $class
     * @return bool
     */
    private function isAllRelationsInitialized(string $class): bool
    {
        $result = $this->relations[$class] ?? null;
        return $result ? ($result['initialized'] ?? false) : false;
    }

    /**
     * [allRelationsInitialized description]
     *
     * @param string $class
     * @param bool   $state
     */
    private function allRelationsInitialized(string $class, bool $state)
    {
        $this->relations[$class]['initialized'] = $state;
    }

    /**
     * Returns the list of configuration parameters "switchable".
     *
     * @return array
     */
    private function getSwitchableProperties(): array
    {
        return [
            'related.setter'       => 'setter',
            'related.getter'       => 'getter',
            'related.mid_property' => 'mid.property',
            'mid.current_property' => 'mid.related_property'
        ];
    }

    /**
     * Returns switched configuration.
     * Used to auto generate related configuration.
     *
     * @param  array  $relation
     * @param  string $related_class
     * @return array
     */
    private function switchPropertiesConfig(array $relation, string $related_class): array
    {
        $to_switch = $this->getSwitchableProperties();
        $to_switch = $to_switch + array_flip($to_switch);

        $switched = [
            'type' => $this->getRelationTypeList('mapped')[$relation['type']] ?? $relation['type'],
            'related.class' => $related_class
        ];

        $unswitchable_config = array_merge(['name'], $this->getMidParamsList());

        $switched = $switched + array_intersect_key($relation, array_flip($unswitchable_config));

        foreach ($to_switch as $field => $switched_field) {
            if (true == isset($relation[$field])) {
                $switched[$switched_field] = $relation[$field];
            }
        }

        return $switched;
    }

    private function mergeConfigBasedOn(
        string $class,
        string $source,
        array $relation,
        string $related_source,
        array $related_config
    ): array {
        $unoverridable_config = array_merge(['type'], $this->getMidParamsList());
        $unoverridable_config = array_combine($unoverridable_config, $unoverridable_config);
        unset($unoverridable_config['mid.property']);

        $intersec = array_intersect_key($relation, $related_config);
        $intersec = array_intersect(array_keys($intersec), $unoverridable_config);

        if (false == empty($intersec)) {
            sort($intersec);
            sort($unoverridable_config);

            $this->throw(
                __LINE__,
                sprintf(
                    'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                    .'Related relation "%s::$%s" already defined ["%s"] parameter(s).'.PHP_EOL
                    .'["%s"] cannot be overrided.',
                    $class,
                    $source,
                    $relation['related.class'],
                    $related_source,
                    implode('", "', $intersec),
                    implode('", "', $unoverridable_config)
                )
            );
        }

        // Prevent configuration conflicts
        foreach ($this->getSwitchableProperties() as $field => $switch_field) {
            if ((isset($relation[$field]) && isset($related_config[$switch_field]))
                || (isset($relation[$switch_field]) && isset($related_config[$field]))
            ) {
                $this->throw(
                    __LINE__,
                    sprintf(
                        'Failed to initialize relation from "%s::$%s".'.PHP_EOL
                        .'Related relation "%s::$%s" pre-configured some parameters for "%s::$%s".'.PHP_EOL
                        .'"%s" and "%s" configuration parameters can\'t be defined at same time.',
                        $class,
                        $source,
                        $relation['related.class'],
                        $related_source,
                        $class,
                        $source,
                        $field,
                        $switch_field
                    )
                );
            }
        }

        // Add defined unoverridable params to $related_config
        $related_config = $related_config + array_intersect_key($relation, array_flip($unoverridable_config));

        // Switch related <=> current configurations properties
        $switched_config = $this->switchPropertiesConfig($related_config, $relation['related.class']);

        // Add missing configuration parameters about related config
        $merged_config = $relation + $switched_config + ['related.property' => $related_source];

        return $merged_config;
    }

    /**
     * [getSnakeCase description]
     *
     * @param  string $string
     * @return string
     */
    private function getSnakeCase(string $string): string
    {
        $pattern = "/(?<caps>[A-Z])/";
        $replacement = "_$1";
        return strtolower(preg_replace($pattern, $replacement, lcfirst($string)));
    }

    /**
     * [getPlurial description]
     *
     * @param  string $string
     * @return string
     */
    private function getPlurial(string $string): string
    {
        if (false !== strpos($string, 'y', -1)) {
            $pattern = "/(?<letter>[y])$/";
            $replacement = "ies";
            return strtolower(preg_replace($pattern, $replacement, $string));
        }

        return $string.'s';
    }

    /**
     * Throws exception.
     *
     * @param  int         $line
     * @param  string      $message
     * @param  string|null $relation_name
     * @param  string|null $class_exception
     * @throws \Exception
     */
    private function throw(
        int $line,
        string $message,
        string $class = null,
        string $relation_name = null,
        string $class_exception = null,
        \Throwable $exception = null
    ) {
        // Add right concerned relation information to the message
        // Help to find and fixe exception
        foreach (($class ? $this->getRelations($class) : []) as $key => $value) {
            if ($relation_name === $key) {
                $extra_message = sprintf(
                    'Check @Relation annotation of :'.PHP_EOL
                    .'"%s::$%s" or'.PHP_EOL
                    .'"%s::$%s" properties.',
                    $class,
                    $value['source'],
                    $value['related.class'],
                    $value['related.property']
                );

                $message = sprintf(
                    '%s'.PHP_EOL.PHP_EOL.'%s',
                    $message,
                    $extra_message
                );

                break;
            }
        }

        $message = sprintf(
            '%s',
            $message
        );

        $this->exception_manager->throw(
            self::class,
            $line,
            $message,
            $class_exception,
            $exception
        );
    }

    /**
     * Checks if the entity class matchs "related.class" config.
     *
     * @param  string         $class
     * @param  string         $relation_name
     * @param  mixed $entity
     * @param  int            $line
     * @param  string|null    $message
     * @throws \Exception if entity type is not allowed
     */
    private function checkRelatedEntityType(
        string $class,
        string $relation_name,
        $entity,
        int $line,
        string $message = null
    ): void {
        $relation = $this->getRelations($class, $relation_name);

        if (false == is_object($entity) || false == is_a($entity, $relation['related.class'])) {
            $this->throw(
                $line,
                sprintf(
                    ($message ? $message . PHP_EOL : '')
                    .'Invalid related entity class.'.PHP_EOL
                    .'"%s" type found, "%s" expected as defined by "related.class" @Relation annotation parameter.',
                    is_object($entity) ? get_class($entity) : gettype($entity),
                    $relation['related.class']
                ),
                $class,
                $relation_name
            );
        }
    }

    /**
     * [getConfigType description]
     *
     * @param  string $class, string $relation_name
     * @return string
     */
    private function getRelationType(string $class, string $relation_name): string
    {
        return $this->getRelations($class, $relation_name)['type'];
    }

    /**
     * [initRelationConfigVar description]
     */
    private function initRelationConfigVar(string $class)
    {
        $this->relations[$class] = $this->relations[$class] ?? [];
        $this->relations[$class]['relations'] = $this->relations[$class]['relations'] ?? [];
    }

    /**
     * [getConfig description]
     *
     * @param  string|null $relation_name
     * @return array
     */
    private function &getRelations(string $class, string $relation_name = null): array
    {
        if (null === $relation_name) {
            $this->initRelationConfigVar($class);
            return $this->relations[$class]['relations'];
        }

        return $this->relations[$class]['relations'][$relation_name];
    }

    private function addRelation(string $class, string $name, array $relation): self
    {
        $this->getRelations($class)[$name] = $relation;
        $this->addRelationNameAlias($class, $name, $relation);

        return $this;
    }

    private function addRelationNameAlias(string $class, string $name, array $relation): self
    {
        $this->relations_names[$class] = $this->relations_names[$class] ?? [];

        $this->relations_names[$class][$relation['source']] = $name;

        return $this;
    }

    private function getRelatedRelationName(string $class, string $relation_name): string
    {
        return $this->getRelations($class, $relation_name)['related.config'];
    }

    private function getRelatedClass(string $class, string $relation_name): string
    {
        return $this->getRelations($class, $relation_name)['related.class'];
    }

    private function midRelationIsDefined(string $class, string $relation_name): bool
    {
        return isset($this->getRelations($class, $relation_name)['mid.factory']);
    }

    private function checkMidRelationDefinition(string $class, string $relation_name)
    {
        if (true == $this->midRelationIsDefined($class, $relation_name)) {
            return ;
        }

        $this->throw(
            __LINE__,
            sprintf(
                'Mid relation is not defined.'.PHP_EOL
                .'Use "mid" parameter of @Relation to define it.',
                $class,
                $method,
                implode('", "', $this->getMidParamsList())
            ),
            $class,
            $relation_name
        );
    }

    private function getMidParamsList(): array
    {
        return [
            'mid.class',
            'mid.factory',
            'mid.property',
            'mid.current_property',
            'mid.related_property',
            'mid.setter',
            'mid.getter'
        ];
    }
}
