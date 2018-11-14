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

namespace PommX\Repository\QueryBuilder\Extension;

use PommX\Repository\QueryBuilder\QueryBuilder;
use PommX\Tools\Exception\ExceptionManagerInterface;

interface ExtensionInterface
{
    /**
     * Apply extension on $query_builder.
     * Goal is to edit its SQL and/or add params.
     *
     * @param  QueryBuilder $query_builder
     * @param  bool         $is_collection
     * @param  array|null       $params
     * @return QueryBuilder
     */
    public function apply(QueryBuilder $query_builder, bool $is_collection, array $params = null): QueryBuilder;

    /**
     * Indicates if this extension is supported depending on arguments.
     *
     * @param  QueryBuilder $query_builder
     * @param  bool         $is_collection
     * @param  array|null       $params
     * @return bool
     */
    public function supports(QueryBuilder $query_builder, bool $is_collection, array $params = null): bool;

    /**
     * Indicates if extension supports "getResults()" method.
     *
     * @param  QueryBuilder $query_builder
     * @param  bool         $is_collection
     * @param  array|null       $params
     * @return bool
     */
    public function supportsResults(QueryBuilder $query_builder, bool $is_collection, array $params = null): bool;

    /**
     * Returns results from $query_builder
     *
     * @param  QueryBuilder $query_builder
     * @param  bool         $is_collection
     * @param  array|null       $params
     * @return mixed
     */
    public function getResults(QueryBuilder $query_builder, bool $is_collection, array $params = null);

    /**
     * Returns extension identifier.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Defines exception manager.
     *
     * @param ExceptionManagerInterface $exception_manager
     */
    public function setExceptionManager(ExceptionManagerInterface $exception_manager);
}
