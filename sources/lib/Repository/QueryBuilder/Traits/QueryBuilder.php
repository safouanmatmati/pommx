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

namespace PommX\Repository\QueryBuilder\Traits;

use PommProject\Foundation\Where;
use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Model\Projection;
use PommProject\Foundation\ResultIterator;

use PommX\Repository\QueryBuilder\QueryBuilder as QueryBuilderClass;
use PommX\Repository\AbstractRepository;
use PommX\Repository\QueryBuilder\Extension\AbstractExtension;
use PommX\Repository\QueryBuilder\Extension\ExtensionsManager;
use PommX\Repository\QueryBuilder\Extension\GroupBy;

use PommX\Entity\AbstractEntity;
use PommX\Fetch\Annotation\Fetch;

trait QueryBuilder
{
    /**
     *
     * @var ExtensionsManager
     */
    private $extensions_manager;

    /**
     * Create a QueryBuilder instance.
     *
     * @param  string          $sql
     * @param  array           $values
     * @param  Projection|null $projection
     * @param  array|null      $params
     * @param  array|null      $context
     * @return QueryBuilderClass
     */
    public function createBuilder(
        string $sql,
        array $values = [],
        Projection $projection = null,
        array $params = null,
        array $context = null
    ): QueryBuilderClass {
        return new QueryBuilderClass(
            $this->getExceptionManager(),
            $this->extensions_manager,
            $this,
            $sql,
            $values,
            $projection,
            $params,
            $context
        );
    }

    /**
     * [getDefaultSelectQueryParams description]
     *
     * @param  array|null $fields [description]
     * @return array               [description]
     */
    protected function getDefaultSelectQueryParams(array $fields = null): array
    {
        $extensions_params = [];
        $self_alias        = $this->getRelationAlias();
        $primary_key_def   = $this->getStructure()->getPrimaryKey();
        $extension_needle  = AbstractExtension::NEEDLE;

        // Default fields are :
        // - all structure fields.
        // - current entity class properties with @Fetch::MODE_JOIN annotation
        if (true == is_null($fields)) {
            $fields = $this->getStructure()->getFieldNames();
            $fields = array_combine($fields, $fields);

            $properties = $this->fetcher_manager->getProperties($this->getEntityClass());

            foreach ($properties as $property => $conf) {
                if ($conf['mode'] == Fetch::MODE_JOIN) {
                    $fields[] = $property;
                    continue;
                }

                unset($fields[$property]);
            }
        }

        // Create projection.
        // It creates fields definitions, needed to convert them back in AbstractEntity instance for example.
        $projection = $this->createProjectionFor(array_merge($fields, $primary_key_def));

        // Retrieves joins, defined for each field having a @Fetch annotation
        $joins = $this->fetcher_manager->getJoins($this->getEntityClass(), $fields);

        foreach ($joins as $join) {
            $join->setSourceAlias($self_alias);

            foreach ($join->getFields() as $field => $data) {
                $projection->setField($field, $data['content'], $data['type']);

                if (false == $projection->isArray($field)) {
                    $extensions_params[GroupBy::OPTION][] = $field;
                }
            }
        }

        $sql = "SELECT :fields FROM :table_self $self_alias :joins WHERE :where AND $extension_needle";

        return [
            'self_alias'        => $self_alias,
            'sql'               => $sql,
            'fields'            => $fields,
            'joins'             => $joins,
            'where'             => new Where(),
            'projection'        => $projection,
            'extensions_params' => $extensions_params
        ];
    }

    /**
     * Collect specific entity of current relation
     * - with all or some structure columns only
     * - ready to be used associated to the extension manager of repository
     *
     * @param  array      $primary_key
     * @param  array|null $fields
     * @param  array|null $context
     * @return AbstractEntity|null
     */
    public function findByPkFromQb(array $primary_key, array $fields = null, array $context = null): ?AbstractEntity
    {
        $primary_key_def = $this->getStructure()->getPrimaryKey();
        $primary_key     = array_combine($primary_key_def, $primary_key);

        $where = $this
            ->checkPrimaryKey($primary_key)
            ->getWhereFrom($primary_key, $this->getRelationAlias());

        $prepared_params   = $this->getDefaultSelectQueryParams($fields, $where);

        $self_alias        = $prepared_params['self_alias'];
        $sql               = $prepared_params['sql'];
        $joins             = $prepared_params['joins'];
        $where             = $where; // $prepared_params['where'];
        $projection        = $prepared_params['projection'];
        $extensions_params = $prepared_params['extensions_params'];

        $replacement = [
            ':fields'     => $projection->formatFieldsWithFieldAlias($self_alias),
            ':table_self' => $this->getStructure()->getRelation(),
            ':joins'      => join(' ', $joins),
            ':where'      => $where
        ];

        return $this->createBuilder(
            strtr($sql, $replacement),
            $where->getValues(),
            $projection,
            $extensions_params,
            $context
        )->getOne();
    }

    /**
     * Collect entities of current relation
     * - with all or some structure columns only
     * - ready to be used associated to the extension manager of repository
     *
     * @param  array|null $fields
     * @param  Where|null $where   [description]
     * @param  array|null $context
     * @return mixed
     */
    public function findAllFromQb(array $fields = null, Where $where = null, array $context = null)
    {
        $prepared_params   = $this->getDefaultSelectQueryParams($fields);

        $self_alias        = $prepared_params['self_alias'];
        $sql               = $prepared_params['sql'];
        $joins             = $prepared_params['joins'];
        $where             = $where ?? $prepared_params['where'];
        $projection        = $prepared_params['projection'];
        $extensions_params = $prepared_params['extensions_params'];

        $replacement = [
            ':fields'     => $projection->formatFieldsWithFieldAlias($self_alias),
            ':table_self' => $this->getStructure()->getRelation(),
            ':joins'      => join(' ', $joins),
            ':where'      => $where
        ];

        return $this->createBuilder(
            strtr($sql, $replacement),
            $where->getValues(),
            $projection,
            $extensions_params,
            $context
        )->getAll();
    }

    /**
     * Defines the extension manager.
     *
     * @param  ExtensionsManager $extensions_manager [description]
     * @return self                                  [description]
     */
    private function initializeQueryBuilderTrait(ExtensionsManager $extensions_manager): self
    {
        $this->extensions_manager = $extensions_manager;
        return $this;
    }
}
