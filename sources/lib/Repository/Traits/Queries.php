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

namespace PommX\Repository\Traits;

use PommProject\ModelManager\Model\ModelTrait\WriteQueries;
use PommProject\ModelManager\Model\Projection;
use PommProject\Foundation\Where;
use PommProject\Foundation\ResultIterator;

use PommX\Entity\AbstractEntity;

trait Queries
{
    use WriteQueries;

    /**
     * {@inheritdoc}
     */
    public function query($sql, array $values = [], Projection $projection = null)
    {
        return parent::query($sql, $values, $projection);
    }

    /**
     * insert
     *
     * Insert entities in the database.
     * They are updated with values returned by the database (ie, default values).
     *
     * @access public
     * @param  AbstractEntity[] $entities
     * @return self
     */
    public function insert(array $entities): self
    {
        if (true == empty($entities)) {
            return $this;
        }

        $entity_class = $this->getEntityClass();

        $fields = $this->getStructure()->getFieldNames();
        sort($fields);
        $groups = [];

        // Organizes insertion by groups (depending on defined fields)
        foreach ($entities as $entity) {
            // Checks validity
            if (false == is_object($entity) || false == is_a($entity, $entity_class)) {
                $message = sprintf(
                    'Failed to insert data.'.PHP_EOL
                    .'"%s" type found, "%s" class expected.',
                    is_object($entity) ? get_class($entity) : gettype($entity),
                    $entity_class
                );

                $this->getExceptionManager()->throw(__LINE__, $message);
            } elseif (false == ($entity->status() === $entity::STATUS_MODIFIED || $entity->status() === $entity::STATUS_NONE)) {
                $message = sprintf(
                    'Failed to insert data.'.PHP_EOL
                    .'Entity status has to be different than "Entity::STATUS_EXIST" and set to "Entity::STATUS_MODIFIED".'
                );

                $this->getExceptionManager()->throw(__LINE__, $message);
            }

            $values = $entity->extract($fields);

            // Filter only defined fields, allows database adds default values
            $values = array_filter(
                $values,
                function ($value) {
                    return false == is_null($value);
                }
            );

            // Groups values with same defined fields to perform multiple insert
            $key = join('_', array_keys($values));
            $groups[$key] = $groups[$key] ?? ['fields' => array_keys($values), 'entities' => []];
            $groups[$key]['entities'][] = ['entity' => $entity, 'values' => $values];
        }

        // Inserts each groups at time but multiple
        foreach ($groups as $group) {
            $all_values = $all_values_str = [];

            $entities = [];
            foreach ($group['entities'] as $data) {
                $all_values_str[] = sprintf('(%s)', join(',', $this->getParametersList($data['values'])));
                $all_values = array_merge($all_values, array_values($data['values']));
                $entities[] = $data['entity'];
            }

            // Defines SQL
            $sql = strtr(
                "INSERT INTO :relation (:fields) VALUES :values RETURNING :projection",
                [
                ':relation'   => $this->getStructure()->getRelation(),
                ':fields'     => $this->getEscapedFieldList($group['fields']),
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
                ':values'     => join(',', $all_values_str)
                ]
            );

            // Hydrate entities with new values, store them in cache, update object status
            $this->queryAndSync(
                $this->query($sql, array_values($all_values)),
                $entities,
                [AbstractEntity::STATUS_NONE => true, AbstractEntity::STATUS_EXIST => true]
            );
        }

        return $this;
    }

    /**
     * Update
     *
     * Update entities in the database.
     * They are updated with values returned by the database (ie, default values).
     *
     * @access public
     * @param  AbstractEntity[] $entities
     * @return self $this
     */
    public function update(array $entities): self
    {
        if (true == empty($entities)) {
            return $this;
        }

        $entity_class = $this->getEntityClass();

        // Define fields to update
        // Select only thoses loaded at the initialization and that are part of structure
        $fields = $this->getStructure()->getFieldNames();
        sort($fields);

        $final_values = [];
        $final_where = new Where();
        $primary_keys = $this->getStructure()->getPrimaryKey();

        // Organizes insertion by groups (depending on defined fields)
        foreach ($entities as $entity) {
            // Checks validity
            if (false == is_object($entity) || false == is_a($entity, $entity_class)) {
                $message = sprintf(
                    'Failed to update data.'.PHP_EOL
                    .'"%s" type found, "%s" class expected.',
                    is_object($entity) ? get_class($entity) : gettype($entity),
                    $entity_class
                );

                $this->getExceptionManager()->throw(__LINE__, $message);
            } elseif (false == $entity->isStatus($entity::STATUS_EXIST, $entity::STATUS_MODIFIED)) {
                $message = sprintf(
                    'Failed to update data.'.PHP_EOL
                    .'Entities status has to be set to "%s::STATUS_EXIST" and "%s::STATUS_MODIFIED".',
                    $class = get_class($entity),
                    $class
                );

                $this->getExceptionManager()->throw(__LINE__, $message);
            }

            $values = $entity->extract($fields);
            $list_values = $this->getParametersList($values);

            // Use initial primary keys as identifers (initial = data from db, stored inside $entity->container variable)
            foreach ($entity->fields($primary_keys) as $field => $value) {
                $list_values[] = $list_values[$field];
                $values[] = $value;
            }

            $all_values_str[] = sprintf('(%s)', join(',', $list_values));
            $final_values = array_merge($final_values, array_values($values));
        }

        // Defines fields
        $to_set = [];
        foreach ($fields as $field) {
            $field = $this->escapeIdentifier($field);
            $to_set[] = sprintf(
                '%s = %s',
                $field,
                'values_alias.'.$field
            );
        }

        // Defines Where
        foreach ($primary_keys as $field) {
            $current_field = $this->escapeIdentifier('current_'.$field);
            $field = $this->escapeIdentifier($field);
            $fields[] = $current_field;

            $final_where->andWhere('relation_alias.'.$field.' = values_alias.'.$current_field);
        }

        // Defines SQL
        $sql = 'UPDATE :relation AS relation_alias '
            .'SET :to_set '
            .'FROM (VALUES :values) AS values_alias (:fields) '
            .'WHERE :where '
            .'RETURNING :projection';

        $sql = strtr(
            $sql,
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':to_set'     => join(', ', $to_set),
                ':fields'     => join(', ', $fields),
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias('relation_alias'),
                ':values'     => join(',', $all_values_str),
                ':where'      => $final_where
            ]
        );

        // Hydrate entities with new values, store them in cache, update object status
        $this->queryAndSync(
            $this->query($sql, array_merge($final_values, $final_where->getValues())),
            array_values($entities),
            [AbstractEntity::STATUS_NONE => true, AbstractEntity::STATUS_EXIST => true]
        );

        return $this;
    }


    /**
     * delete
     *
     * Delete entities in the database.
     *
     * @access public
     * @param  array $entities
     * @return self $this
     */
    public function delete(array $entities)
    {
        if (true == empty($entities)) {
            return $this;
        }

        $all_values   = [];
        $entity_class = $this->getEntityClass();
        $primary_keys = $this->getStructure()->getPrimaryKey();
        sort($primary_keys);
        $where = new Where();

        foreach ($entities as $entity) {
            // Checks validity
            if (false == is_object($entity) || false == is_a($entity, $entity_class)) {
                $message = sprintf(
                    'Failed to delete data.'.PHP_EOL
                    .'"%s" type found, "%s" class expected.',
                    is_object($entity) ? get_class($entity) : gettype($entity),
                    $entity_class
                );

                $this->getExceptionManager()->throw(__LINE__, $message);
            } elseif (($values = $entity->fields($primary_keys)) // Use initial primary keys as identifers
                && false == ($entity->isStatus($entity::STATUS_EXIST, $entity::STATUS_TO_DELETE))
            ) {
                $keys = [];
                foreach ($values as $key => $value) {
                    $keys[] = sprintf('"%s" => "%s"', $key, $value);
                }

                $message = sprintf(
                    'Failed to delete data.'.PHP_EOL
                    .'Entity, with primary keys [%s], hasn\'t status set to "Entity::STATUS_EXIST" and "Entity::STATUS_TO_DELETE".',
                    implode(', ', $keys)
                );

                $this->getExceptionManager()->throw(__LINE__, $message);
            }

            // Defines Where
            if (1 === count($primary_keys)) {
                $all_values[] = current($values);
            } else {
                $sub_where = new Where();

                foreach ($values as $field => $value) {
                    $sub_where->andWhere("$field = $*", [$value]);
                }
                $where->orWhere($sub_where);
            }
        }

        if (1 === count($primary_keys)) {
            $where = Where::createWhereIn(current($primary_keys), $all_values);
        }

        // Hydrate entities with new values, store them in cache, set object status
        $this->queryAndSync(
            $this->deleteWhere($where),
            $entities,
            [AbstractEntity::STATUS_DELETED => true]
        );

        return $this;
    }

    /**
     * Return an entity upon its primary key if they are defined or its values.
     * If no entity are found, null is returned.
     *
     * @param  array $values
     * @return AbstractEntity|null
     */
    public function findOneFrom(array $values): ?AbstractEntity
    {
        $primary_key_defined = true;
        $primary_key         = [];

        foreach ($this->getStructure()->getPrimaryKey() as $name) {
            if (true == is_null($values[$name] ?? null)) {
                $primary_key_defined = false;
                break;
            }
            $primary_key[$name] = $values[$name];
        }

        if (true == $primary_key_defined) {
            return $this->findByPK($primary_key);
        }

        return null;
    }

    /**
     * Deletes rows by grouped values and columns.
     * Each column is seperated with a "OR" statement.
     *
     * @param  array $data
     * @return array
     */
    public function deleteGrouped(array $data): array
    {
        $where = new Where();
        foreach ($data as $column => $values) {
            $where->orWhere(Where::createWhereIn($column, $values));
        }

        $entities = [];
        foreach ($this->deleteWhere($where) as $entity) {
            $entity->setStatus([AbstractEntity::STATUS_DELETED => true]);
            $entities[$entity->getHash()] = $entity;
        }

        return $entities;
    }

    private function queryAndSync(ResultIterator $collection, array $entities, array $status = null): array
    {
        foreach ($collection as $index => $entity) {
            if (false == isset($entities[$index])) {
                $this->getExceptionManager()->throw(
                    __LINE__,
                    sprintf(
                        'Failed to merge entity values with thoses returned by statement.'.PHP_EOL
                        .'Entity not found.'
                    )
                );
            }

            // Hydrates entity with values returned by $collection
            // Replaces in cache by existing one
            $this->cache_manager->replace(
                $entities[$index],
                $entity,
                $this->getStructure()->getPrimaryKey()
            );
            $entities[$index]->status($entity->status());

            if (false == is_null($status)) {
                $entities[$index]->setStatus($status);
            }
        }

        return $entities;
    }

    /**
     * getWhereFrom
     *
     * Build a condition on given values.
     *
     * @param  array $values
     * @param  string|null $alias  [description]
     * @return Where
     */
    public function getWhereFrom(array $values, string $alias = null)
    {
        $where = new Where();
        $alias = $alias ? $alias.'.' : '';

        foreach ($values as $field => $value) {
            $where->andWhere(
                sprintf(
                    "%s = $*::%s",
                    $alias.$this->escapeIdentifier($field),
                    $this->getStructure()->getTypeFor($field)
                ),
                [$value]
            );
        }

        return $where;
    }

    protected function createProjectionFor(array $fields = null): Projection
    {
        $projection = $this->createProjection();

        if (true == is_array($fields)) {
            foreach ($projection->getFieldNames() as $field) {
                if (false == in_array($field, $fields)) {
                    $projection->unsetField($field);
                }
            }
        }

        return $projection;
    }

    /**
     * querySingleValue
     *
     * Fetch a single value named « result » from a query.
     * The query must be formatted with ":condition" as WHERE condition
     * placeholder. If the $where argument is a string, it is turned into a "Where" class instance.
     *
     * @access protected
     * @param  string $sql
     * @param  mixed  $where
     * @param  array  $values
     * @return mixed
     */
    public function querySingleValue($sql, $where, array $values)
    {
        return $this->fetchSingleValue($sql, $where, $values);
    }
}
