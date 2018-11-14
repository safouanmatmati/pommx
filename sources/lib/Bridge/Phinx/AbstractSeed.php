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

use Phinx\Seed\AbstractSeed as PhinxAbstractSeed;
use Phinx\Db\Table;

use PommX\Bridge\Phinx\PostgresAdapterAwareTrait;
use PommX\Bridge\Phinx\Dumper;

use PommX\Bridge\Phinx\PommAwareInterface;
use PommX\Bridge\Phinx\PommAwareTrait;

abstract class AbstractSeed extends PhinxAbstractSeed implements PommAwareInterface
{
    use PommAwareTrait;
    use PostgresAdapterAwareTrait;

    /**
     * [private description]
     *
     * @var Table[]
     */
    private $tables;

    /**
     * Filter data with table columns definition
     *
     * @param  Table $table
     * @param  array $data
     * @return array
     */
    protected function filter(Table $table, array $data): array
    {
        $filtered = [];

        foreach ($table->getColumns() as $columns) {
            if (true == array_key_exists($columns->getName(), $data)) {
                $filtered[$columns->getName()] = $data[$columns->getName()];
            }
        }

        return $filtered;
    }


    /**
     * {@inheritdoc}
     * Add data filtering
     *
     * @param  Table $table
     * @param  array $data
     * @return array
     */
    public function insert($table, $data)
    {
        // convert to table object
        if (true == is_string($table)) {
            $table = new Table($table, [], $this->getAdapter());
        }

        $this->tables[] = $table;

        parent::insert($table, $this->filter($table, $data));
    }

    /**
     * Execute "save()" on each table registered.
     *
     * @return self [description]
     */
    public function save(): self
    {
        foreach ($this->tables as $table) {
            $table->save();
        }

        return $this;
    }

    /**
     * [dumpInserted description]
     *
     * @param  Table|null $table [description]
     * @return self         [description]
     */
    public function dumpInserted(Table $table = null, int $expected_insert = null): self
    {
        $dumper   = new Dumper();
        $inserted = $dumper->addToQueue($this->getPostgresAdapter()->getStatementsHistory(), 'INSERT');
        $dumper->addToQueue($this->getPostgresAdapter()->getInsertedHistory($table));

        if (false == is_null($expected_insert)) {
            $dumper->addToQueue(sprintf('%d/%d row(s) inserted.', $inserted, $expected_insert));
        } else {
            $dumper->addToQueue(sprintf('%d row(s) inserted.', $inserted));
        }

        $dumper->dump();

        return $this;
    }
}
