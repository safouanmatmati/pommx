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

namespace PommX\Repository\QueryBuilder\Extension\Pagination;

use PommX\Repository\QueryBuilder\Extension\AbstractExtension;
use PommX\Repository\QueryBuilder\Extension\ExtensionInterface;
use PommX\Repository\QueryBuilder\Extension\GroupBy;
use PommX\Repository\QueryBuilder\Extension\OrderBy;
use PommX\Repository\QueryBuilder\Extension\Pagination\OffsetPager;
use PommX\Repository\QueryBuilder\QueryBuilder;
use PommX\Repository\EmptyResultIterator;

class Pagination extends AbstractExtension implements ExtensionInterface
{
    /**
     * [private description]
     *
     * @var GroupBy
     */
    private $group_by_extension;

    /**
     * [private description]
     *
     * @var OrderBy
     */
    private $order_by_extension;
    /**
     * [items_per_page description]
     *
     * @var integer
     */
    const ITEMS_PER_PAGE = 10;
    const PARAM_PREFIX = 'pagination_';

    /**
     * [__construct description]
     *
     * @param GroupBy $group_by_extension [description]
     * @param OrderBy $order_by_extension [description]
     */
    public function __construct(GroupBy $group_by_extension, OrderBy $order_by_extension)
    {
        $this->group_by_extension = $group_by_extension;
        $this->order_by_extension = $order_by_extension;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $query_builder, bool $is_collection, array $context = null): QueryBuilder
    {
        if (true === $this->isCursor($query_builder, $context)) {
            $this->applyWithCursor($query_builder, $is_collection, $context);
        }

        return $this->applyWithOffset($query_builder, $is_collection, $context);
    }

    private function applyWithOffset(
        QueryBuilder $query_builder,
        bool $is_collection,
        array $context = null
    ): QueryBuilder {
        self::checkNeedle($query_builder->getSql());

        $offset         = $this->getParam($query_builder, 'offset');
        $items_per_page = $this->getParam($query_builder, 'items_per_page')
            ?? self::ITEMS_PER_PAGE;

        if ($offset < 0) {
            $this->getExceptionManager()->throw(
                self::class,
                __LINE__,
                sprintf("'offset' cannot be < 0. (%d given)", $offset)
            );
        }

        if ($items_per_page <= 0) {
            $this->getExceptionManager()->throw(
                self::class,
                __LINE__,
                sprintf(
                    "'items_per_page' must be strictly positive (%d given).",
                    $items_per_page
                )
            );
        }

        $this->order_by_extension->apply($query_builder, $is_collection, $context);
        $this->group_by_extension->apply($query_builder, $is_collection, $context);

        $sql = $query_builder->getSql();

        // Execute an axtra query to retrieves total count
        if (true !== $this->getParam($query_builder, 'is_partial')) {
            $count_sql = sprintf(
                "select count(subquery.*) as result FROM (%s) as subquery",
                $sql
            );

            $count_sql = $this->removeExtensionsNeedle($count_sql);

            $count = $query_builder
                ->getRepository()
                ->querySingleValue($count_sql, '', $query_builder->getValues());

            $this->setParam($query_builder, 'count', $count);
        }

        $sql = sprintf(
            '%s offset %d limit %d',
            $sql,
            $offset,
            $items_per_page
        );

        $query_builder->setSql($sql);

        return $query_builder;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        $this->getParam($query_builder, 'support') ?? $this->setParam($query_builder, 'support', false);

        if (false == parent::supports($query_builder, $is_collection, $context)) {
            return false;
        }

        if (true === $this->supportsWithCursor($query_builder, $is_collection, $context)) {
            return true;
        }

        return $this->supportsWithOffset($query_builder, $is_collection, $context);
    }

    private function supportsWithOffset(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        return $is_collection;
    }

    public function supportsResults(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        if (true === $this->supportsResultsWithCursor($query_builder, $is_collection, $context)) {
            return true;
        }

        return $this->supportsResultsWithOffset($query_builder, $is_collection, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResultsWithOffset(
        QueryBuilder $query_builder,
        bool $is_collection,
        array $context = null
    ): bool {
        return $this->supportsWithOffset($query_builder, $is_collection, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getResults(QueryBuilder $query_builder, bool $is_collection, array $context = null)
    {
        self::checkSupportsResults($query_builder, $is_collection, $context);

        if (true === $this->supportsResultsWithCursor($query_builder, $is_collection, $context)) {
            return $this->getResultsWithCursor($query_builder, $is_collection, $context);
        }

        return $this->getResultsWithOffset($query_builder, $is_collection, $context);
    }

    /**
     * {@inheritdoc}
     */
    private function getResultsWithOffset(QueryBuilder $query_builder, bool $is_collection, array $context = null)
    {
        if (0 === ($count = $this->getParam($query_builder, 'count'))) {
            $iterator = EmptyResultIterator::getInstance($query_builder->getRepository()->getSession());
        } else {
            $iterator = $query_builder->execute();
        }

        $offset         = $this->getParam($query_builder, 'offset');
        $items_per_page = $this->getParam($query_builder, 'items_per_page');

        // Pager
        return new OffsetPager(
            $iterator,
            $items_per_page,
            $offset,
            $count
        );
    }

    private function supportsWithCursor(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        return $is_collection && $this->isCursor($query_builder, $context);
    }

    /**
     * Indicates if cursor mode is used.
     *
     * @param  QueryBuilder $query_builder
     * @param  array        $context
     * @return bool
     */
    private function isCursor(QueryBuilder $query_builder, array $context = null): bool
    {
        return $this->getParam($query_builder, 'use_cursor')
            ?? $context[self::PARAM_PREFIX.'use_cursor'] ?? false;
    }

    private function applyWithCursor(
        QueryBuilder $query_builder,
        bool $is_collection,
        array $context = null
    ): QueryBuilder {
        die(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResultsWithCursor(
        QueryBuilder $query_builder,
        bool $is_collection,
        array $context = null
    ): bool {
        return $this->supportsWithCursor($query_builder, $is_collection, $context) && false;
    }

    /**
     * {@inheritdoc}
     */
    private function getResultsWithCursor(QueryBuilder $query_builder, bool $is_collection, array $context = null)
    {
        die(__METHOD__);
        if ($this->getParam($query_builder, 'count') === 0) {
            $iterator = EmptyResultIterator::getInstance($query_builder->getRepository()->getSession());
        } else {
            $iterator = $this->getRepository()->query(
                sprintf(
                    "%s offset %d limit %d",
                    $query_builder->getSql(),
                    $this->getParam($query_builder, 'offset'),
                    $this->getParam($query_builder, 'items_per_page')
                ),
                $query_builder->getValues(),
                $query_builder->getProjection()
            );
        }

        return [
            'iterator' => $iterator,
            'items_per_page' => $items_per_page,
            'offset' => $offset,
            'count' => $count
        ];

        $results = $query_builder->execute();

        if (false == $is_collection) {
            return $results->current();
        }

        return $results;
    }
}
