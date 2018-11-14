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

namespace PommX\Inspector;

use PommProject\Foundation\Inspector\Inspector as PommInspector;
use PommProject\Foundation\Where;

/**
 * Add functionalities to Pomm default inspector.
 * Foreign keys, type enum and not null definition are now available.
 */
class Inspector extends PommInspector
{
    /**
     * Returns table foreign keys.
     *
     * @param  int $table_oid
     * @return array
     */
    public function getForeignKey(int $table_oid): array
    {
        $sql = <<<SQL
            SELECT
              n.nspname, t.relname, fa.attname as real_attname, a.attname, c.conname
            FROM
              pg_catalog.pg_constraint c
              JOIN
                pg_catalog.pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey)
              JOIN
                pg_catalog.pg_attribute fa ON fa.attrelid = c.confrelid AND fa.attnum = ANY (c.confkey)
                JOIN
                  pg_catalog.pg_class t ON t.oid = fa.attrelid
                  JOIN
                    pg_catalog.pg_namespace n ON n.oid = t.relnamespace
            WHERE
              :condition
            ORDER BY
              n.nspname ASC, t.relname ASC, fa.attname ASC, a.attname ASC, c.conname ASC, array_position(c.conkey, a.attnum) ASC
SQL;

        $condition =
            Where::create('a.attrelid = $*', [$table_oid])
            ->andWhere('c.contype = $*', ['f']);

        $results = [];

        foreach ($this->executeSql($sql, $condition) as $row) {
            $complete_index = sprintf(
                '%s.%s.%s',
                $row['nspname'],
                $row['relname'],
                $row['real_attname']
            );
            $results[$row['attname']] = $complete_index;
        }

        return $results;
    }

    /**
     * Return table columns not null definition.
     *
     * @param  int $table_oid
     * @return array
     */
    public function getNotNull(int $table_oid): array
    {
        $sql = <<<SQL
          SELECT
            attname, attnotnull
          FROM
            pg_catalog.pg_attribute a
          WHERE
            :condition
          ORDER BY
            attname
SQL;
        $condition = Where::create('a.attrelid = $*', [$table_oid])
            ->andWhere('a.attnum > $*', [0]);

        $results = [];

        foreach ($this->executeSql($sql, $condition) as $row) {
            $results[$row['attname']] = $row['attnotnull'];
        }

        return $results;
    }

    /**
     * Returns type enum attributes with their values ordered.
     *
     * @access public
     * @param  int $table_oid
     * @return array
     */
    public function getTypeEnum(int $table_oid): array
    {
        $sql = <<<SQL
          WITH enums as (
            SELECT
              enumtypid, a.attname, enumlabel
            FROM
              pg_catalog.pg_enum e
            JOIN
              pg_catalog.pg_attribute a ON a.atttypid = e.enumtypid
            WHERE
              :condition
            ORDER BY
              enumsortorder
          )
          SELECT
            attname, array_agg(enumlabel) as labels from enums
          GROUP BY
            attname
SQL;

        $condition = Where::create('a.attrelid = $*', [$table_oid]);
        $results   = [];

        foreach ($this->executeSql($sql, $condition) as $row) {
            $results[$row['attname']] = $row['labels'];
        }

        return $results;
    }
}
