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

use Pommx\Repository\QueryBuilder\QueryBuilder;

use Pommx\Tools\Exception\ExceptionManagerAwareTrait;
use Pommx\Tools\Exception\ExceptionManagerAwareInterface;

abstract class AbstractExtension implements ExceptionManagerAwareInterface
{
    use ExceptionManagerAwareTrait;

    const NEEDLE = ':extensions_needle';

    /**
     * {@inheritdoc}
     */
    public function supports(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        return $this->getParam($query_builder, 'support') ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function getResults(QueryBuilder $query_builder, bool $is_collection, array $context = null)
    {
        $this->checkSupportsResults($query_builder, $is_collection, $context);

        $results = $query_builder->execute();

        if (false == $is_collection) {
            return $results->current();
        }

        return $results;
    }

    /**
     * Returns extension identifier.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return static::class;
    }

    /**
     * Checks if needle exists in string.
     *
     * @param  string $sql
     * @throws \LogicException  if needle not found
     */
    protected function checkNeedle(string $sql)
    {
        if (false === strpos($sql, self::NEEDLE)) {
            $this->getExceptionManager()->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to apply extension.'.
                    '"%s" needle is missing from SQL statement.'.PHP_EOL
                    .'Needle is required to apply extension modifications.',
                    self::NEEDLE
                )
            );
        }
    }

    private function checkParamPrefix()
    {
        if (false == defined('static::PARAM_PREFIX')) {
            $this->getExceptionManager()
                ->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to set/get parameter from "%s" extension.'.PHP_EOL
                        .'Constant "%s::PARAM_PREFIX" doesn\'t exists, it must be defined.',
                        static::class,
                        static::class
                    )
                );
        }
    }

    /**
     * [checkSupportsResults description]
     * @param  QueryBuilder $query_builder
     * @param  bool         $is_collection
     * @param  [type]       $context
     * @throws \LogicException if it doesn't supports results
     * @return [type]
     */
    protected function checkSupportsResults(QueryBuilder $query_builder, bool $is_collection, array $context = null)
    {
        if (false == $this->supportsResults($query_builder, $is_collection, $context)) {
            $this->getExceptionManager()
                ->throw(
                    self::class,
                    __LINE_,
                    sprintf(
                        'Failed to call "%s".'.PHP_EOL
                        .'This extension doesn\'t support results.'.PHP_EOL
                        .'Use "%s::%s" to check support results before calling this method.',
                        __METHOD__,
                        self::class,
                        'supportsResults()'
                    )
                );
        }
    }

    /**
     * Returns a parameter from $query_builder depending on extension.
     * $name is prefixed with extension PARAM_PREFIX constant.
     *
     * @param  QueryBuilder $query_builder
     * @param  string       $name
     * @return mixed
     */
    protected function getParam(QueryBuilder $query_builder, string $name)
    {
        $this->checkParamPrefix();

        return $query_builder->getParam(static::PARAM_PREFIX.$name);
    }

    /**
     * Add a parameter to $query_builder depending on extension.
     * $name is prefixed with extension PARAM_PREFIX constant.
     *
     * @param QueryBuilder $query_builder
     * @param string       $name
     * @param mixed        $value
     */
    protected function setParam(QueryBuilder $query_builder, string $name, $value)
    {
        $this->checkParamPrefix();

        return $query_builder->addParam(static::PARAM_PREFIX.$name, $value);
    }

    /**
     * Returns $sql string with AbstractExtension::NEEDLE reference replaced by "TRUE".
     *
     * @param  string $sql
     * @return string
     */
    public static function removeExtensionsNeedle(string $sql): string
    {
        return strtr($sql, [AbstractExtension::NEEDLE => 'TRUE']);
    }
}
