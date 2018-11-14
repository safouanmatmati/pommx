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

namespace PommX\Bridge\Phinx;

use Phinx\Migration\AbstractMigration as PhinxAbstractMigration;
use Phinx\Db\Adapter\AdapterFactory;

use PommX\Bridge\Phinx\PostgresAdapterAwareTrait;

use PommX\Bridge\Phinx\PommAwareInterface;
use PommX\Bridge\Phinx\PommAwareTrait;

abstract class AbstractMigration extends PhinxAbstractMigration implements PommAwareInterface
{
    use PommAwareTrait;
    use PostgresAdapterAwareTrait;

    /**
     * Add definiton of new custom enum
     *
     * @param string $typename
     */
    public function createEnumType(string $typename, array $values): void
    {
        $typename = $this->getSchemaName().'."' . $typename . '"';

        $sql = sprintf("CREATE TYPE %s AS ENUM ('%s');", $typename, implode("','", $values));

        $this->execute($sql);
    }

    /**
     * [getAliasEnum description]
     *
     * @param  string $typename
     * @return string
     */
    public function getAliasEnumType(string $typename): string
    {
        return $this->getPostgresAdapter()->getAliasEnumType($typename);
    }
}
