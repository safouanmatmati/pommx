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

use PommX\Repository\QueryBuilder\Extension\AbstractExtension;
use PommX\Repository\QueryBuilder\Extension\ExtensionInterface;
use PommX\Repository\QueryBuilder\QueryBuilder;

class GroupBy extends AbstractExtension implements ExtensionInterface
{
    const PARAM_PREFIX = 'group_by_';
    const OPTION = 'group_by';

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $query_builder, bool $is_collection, array $context = null): QueryBuilder
    {
        self::checkNeedle($sql = $query_builder->getSql());

        $repository = $query_builder->getRepository();

        $columns = [];

        foreach ($repository->getStructure()->getPrimaryKey() as $name) {
            $columns[$repository->getAliasedIdentifier($name)] =
                $repository->getAliasedIdentifier($name);
        }

        if (false == is_null($custom_group_by = $query_builder->getParam(self::OPTION))) {
            if (true == is_array($custom_group_by)) {
                foreach ($custom_group_by as $column) {
                    if (false == is_string($column)) {
                        $this->getExceptionManager()->throw(
                            self::class,
                            __LINE__,
                            sprintf(
                                'Failed to apply extension.'.PHP_EOL
                                .'"group_by" parameter found in "QueryBuilder", but the format is invalid.'.PHP_EOL
                                .'"%s" type found, "string" expected.'
                            )
                        );
                    }
                }

                $columns = array_merge($columns, $custom_group_by);
            } elseif (false == is_string($custom_group_by)) {
                $this->getExceptionManager()->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to apply extension.'.PHP_EOL
                        .'"group_by" parameter found in "QueryBuilder", but the format is invalid.'.PHP_EOL
                        .'"%s" type found, "string" or "string[]" expected.'
                    )
                );
            } else {
                $columns[$custom_group_by] = $custom_group_by;
            }
        }

        $sql = strtr($sql, [
            self::NEEDLE => sprintf(
                '%s GROUP BY :columns',
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
