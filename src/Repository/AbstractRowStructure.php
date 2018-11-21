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

namespace Pommx\Repository;

use PommProject\ModelManager\Model\RowStructure;

abstract class AbstractRowStructure extends RowStructure
{
    protected $foreign_key = [];
    protected $not_null    = [];
    protected $type_enum   = [];

    /**
     * {@inheritdoc}
     */
    public function setPrimaryKey(array $primary_key)
    {
        $data = [];
        foreach ($primary_key as $name) {
            $data[$this->getRelation().'.'.$name] = $name;
        }

        asort($data);

        parent::setPrimaryKey($data);

        $this->addPrimaryKeyNotNull();

        return $this;
    }

    /**
     * setForeignKey
     *
     * Set or change the foreign key definition.
     *
     * @access public
     * @param  array $foreign_key
     * @return RowStructure $this
     */
    public function setForeignKey(array $foreign_key): self
    {
        $this->foreign_key = $foreign_key;

        return $this;
    }

    /**
     * setNotNull
     *
     * @access public
     * @param  array $not_null
     * @return RowStructure $this
     */
    public function setNotNull(array $not_null): self
    {
        foreach ($not_null as $name => $value) {
            $not_null[$name] = (bool) ($value);
        }

        $this->not_null = $not_null;

        $this->addPrimaryKeyNotNull();

        return $this;
    }

    private function addPrimaryKeyNotNull(): self
    {
        foreach ($this->getPrimaryKey() as $name) {
            $this->not_null[$name] = true;
        }

        return $this;
    }

    /**
     * setTypeEnum
     *
     * @access public
     * @param  array $not_null
     * @return RowStructure $this
     */
    public function setTypeEnum(array $type_enum): self
    {
        $this->type_enum = $type_enum;

        return $this;
    }

    /**
     * setForeignKey
     *
     * Set or change the foreign key definition.
     *
     * @access public
     * @param  array $foreign_key
     * @return RowStructure $this
     */
    public function getForeignKey(): array
    {
        return $this->foreign_key;
    }

    /**
     * getNotNull
     *
     * @access public
     * @param  array
     */
    public function getNotNull(): array
    {
        return $this->not_null;
    }

    /**
     * [isNotNull description]
     *
     * @param  string $column [description]
     * @return bool           [description]
     */
    public function isNotNull(string $column): bool
    {
        return true == $this->not_null[$column];
    }

    /**
     * getTypeEnum
     *
     * @access public
     * @return array
     */
    public function getTypeEnum(): array
    {
        return $this->type_enum;
    }

    /**
     * Returns [current foreign key => related primary key] relation between this structure and a related one.
     *
     * @param  AbstractRowStructure $related_structure [description]
     * @return array|null                         [description]
     */
    public function getForeignKeyRelatedTo(AbstractRowStructure $related_structure): ?array
    {
        $related_pk = $related_structure->getPrimaryKey();
        $current_fk = $this->getForeignKey();

        // Check from current foreign key which ones point to related primary key
        $concerned_fk = array_intersect($current_fk, array_flip($related_pk));

        // Replace current foreign key fullname with the their short name
        foreach ($concerned_fk as $fk => $full_name) {
            $concerned_fk[$fk] = $related_pk[$full_name];
        }

        return $concerned_fk;
    }
}
