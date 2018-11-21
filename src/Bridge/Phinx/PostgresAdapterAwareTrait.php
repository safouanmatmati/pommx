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

namespace Pommx\Bridge\Phinx;

use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;

use Pommx\Bridge\Phinx\PostgresAdapter;
use Pommx\Bridge\Phinx\SchemaTrait;

trait PostgresAdapterAwareTrait
{
    use SchemaTrait;

    /**
     * [init description]
     */
    protected function init()
    {
        // Replaces default Postgres adapter
        AdapterFactory::instance()->registerAdapter('pgsql', PostgresAdapter::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        parent::setAdapter($adapter);

        // Resets history of previous statements, insert etc... registred by connexion
        $this->getPostgresAdapter()->resetHistory();
    }

    /**
     * [getPostgresAdapter description]
     *
     * @return PostgresAdapter [description]
     */
    public function getPostgresAdapter(): PostgresAdapter
    {
        return $this->getAdapter()->getAdapter();
    }

    /**
     * [dumpInserted description]
     *
     * @param  Table $table [description]
     * @return self         [description]
     */
    public function dumpQueries($filters = null, int $expected_queries_count = null): self
    {
        $dumper = new Dumper();
        $count = $dumper->addToQueue($this->getPostgresAdapter()->getStatementsHistory(), $filters);

        if (false == is_null($expected_queries_count)) {
            $dumper->addToQueue(sprintf('%d/%d queries.', $count, $expected_queries_count));
        } else {
            $dumper->addToQueue(sprintf('%d queries.', $count));
        }

        $dumper->dump();

        return $this;
    }
}
