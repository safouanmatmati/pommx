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

namespace Pommx\Repository\QueryBuilder\Extension;

use Pommx\Repository\QueryBuilder\Extension\AbstractExtension;
use Pommx\Repository\QueryBuilder\Extension\ExtensionInterface;
use Pommx\Repository\QueryBuilder\QueryBuilder;

class OrderBy extends AbstractExtension implements ExtensionInterface
{
    const PARAM_PREFIX = 'order_by_';
    const OPTION = 'order_by';

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $query_builder, bool $is_collection, array $context = null): QueryBuilder
    {
        self::checkNeedle($sql = $query_builder->getSql());

        $repository = $query_builder->getRepository();
        $columns = [];
        $direction = $this->getParam($query_builder, 'direction') ?? 'ASC';

        foreach ($repository->getStructure()->getPrimaryKey() as $name) {
            $columns[$repository->getAliasedIdentifier($name)] =
                $repository->getAliasedIdentifier($name).' '.$direction;
        }

        if (false == is_null($custom_order_by = $query_builder->getParam(self::OPTION))) {
            if (true == is_array($custom_order_by)) {
                foreach ($custom_order_by as $column) {
                    if (false == is_string($column)) {
                        $this->getExceptionManager()->throw(
                            self::class,
                            __LINE__,
                            sprintf(
                                'Failed to apply extension.'.PHP_EOL
                                .'"order_by" parameter found in "QueryBuilder", but the format is invalid.'.PHP_EOL
                                .'"%s" type found, "string" expected.'
                            )
                        );
                    }
                }

                $columns = array_merge($columns, $custom_order_by);
            } elseif (false == is_string($custom_order_by)) {
                $this->getExceptionManager()->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to apply extension.'.PHP_EOL
                        .'"order_by" parameter found in "QueryBuilder", but the format is invalid.'.PHP_EOL
                        .'"%s" type found, "string" or "string[]" expected.'
                    )
                );
            } else {
                $columns[$custom_order_by] = $custom_order_by.' '.$direction;
            }
        }

        $sql = strtr($sql, [
            self::NEEDLE => sprintf(
                '%s ORDER BY :columns',
                self::NEEDLE
            )
        ]);

        $query_builder->setSql(strtr($sql, [':columns' => join(', ', $columns)]));

        return $query_builder;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResults(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        return false;
    }
}
