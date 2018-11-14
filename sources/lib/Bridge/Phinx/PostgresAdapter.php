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

use Phinx\Db\Adapter\PostgresAdapter as PhinxPostgresAdapter;
use Phinx\Db\Table\Column;
use Phinx\Db\Table;

use PommX\Bridge\Phinx\PDO;

class PostgresAdapter extends PhinxPostgresAdapter
{
    /**
     * [private description]
     *
     * @var array
     */
    private $history;

    /**
     * Force use of {@see PommX\Bridge\Phinx\PDO} connection instead of {@see \PDO}.
     *
     * @param \PDO $db
     */
    public function setConnection($db)
    {
        if (false == is_a($db, PDO::class)) {
            $db = null;
            $options = $this->getOptions();

            // if port is specified use it, otherwise use the PostgreSQL default
            if (isset($options['port'])) {
                $dsn = 'pgsql:host=' . $options['host'] . ';port=' . $options['port'] . ';dbname=' . $options['name'];
            } else {
                $dsn = 'pgsql:host=' . $options['host'] . ';dbname=' . $options['name'];
            }

            try {
                $db = new PDO($dsn, $options['user'], $options['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            } catch (\PDOException $exception) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'There was a problem connecting to the database: %s',
                        $exception->getMessage()
                    )
                );
            }
        }

        return parent::setConnection($db);
    }

    /**
     * Add types
     * {@inheritdoc}
     */
    public function getColumnTypes()
    {
        return array_merge(parent::getColumnTypes(), ['enum', 'varchar']);
    }

    /**
     * {@inheritdoc}
     */
    public function isValidColumnType(Column $column)
    {
        return ($this->isEnumType($column->getType()) || parent::isValidColumnType($column));
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlType($type, $limit = null)
    {
        if ('varchar' === $type) {
            return ['name' => 'varchar'];
        }

        if (true == $this->isEnumType($type)) {
            return ['name' => $this->cleanEnumTypePrefix($type)];
        }

        return parent::getSqlType($type, $limit);
    }

    /**
     * [cleanEnumType description]
     *
     * @param  string $type
     * @return string
     */
    private function cleanEnumTypePrefix(string $type): string
    {
        $prefix = $this->getPrefixeAliasEnumType();
        $pattern = "/^(?<prefix>$prefix)(?<name>[[:alpha:]_-]+)$/";
        preg_match($pattern, $type, $matches);

        return $matches['name'];
    }

    /**
     * [isEnumType description]
     *
     * @param  string $type
     * @return bool
     */
    private function isEnumType(string $type): bool
    {
        // Enum case
        $prefix = $this->getPrefixeAliasEnumType();
        $pattern = "/^(?<prefix>$prefix)(?<name>[[:alpha:]_-]+)$/";

        return 1 === preg_match($pattern, $type, $matches);
    }

    /**
     * [getAliasEnumType description]
     *
     * @param  string $type
     * @return string
     */
    public function getAliasEnumType(string $type): string
    {
        return $this->getPrefixeAliasEnumType().$type;
    }

    /**
     * [getPrefixeAliasEnumType description]
     *
     * @return string
     */
    private function getPrefixeAliasEnumType(): string
    {
        return 'enum-';
    }

    /**
     * {@inheritdoc}
     *
     * Register insert history.
     */
    public function insert(Table $table, $row): self
    {
        parent::insert($table, $row);

        $this->registerInsertedHistory($table, $row);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Register insert history.
     */
    public function bulkinsert(Table $table, $rows): self
    {
        parent::bulkinsert($table, $rows);

        $this->registerInsertedHistory($table, $rows, true);

        return $this;
    }

    /**
     * Register insert history.
     *
     * @param  Table $table [description]
     * @param  array $rows  [description]
     * @return self          [description]
     */
    private function registerInsertedHistory(Table $table, $rows, bool $is_bulk = null): self
    {
        $history = &$this->history;

        $addHistory = function ($row) use (&$history, $table) {
            $message = [];
            foreach ($row as $key => $val) {
                $message[] = sprintf("'%s': '%s'", $key, $val);
            }

            $message = sprintf('VALUES details : (%s)', join(', ', $message));

            $history['inserted'][$table->getName()][] = $message;
        };

        if (true === $is_bulk) {
            array_map(
                $addHistory,
                $rows
            );
        } else {
            $addHistory($rows);
        }

        return $this;
    }

    /**
     * Clear history.
     *
     * @return self [description]
     */
    public function resetHistory(): self
    {
        $this->clearInsertedHistory();
        $this->clearStatementsHistory();

        return $this;
    }

    /**
     * Returns history of executed insert.
     *
     * @param  Table|null $table [description]
     * @return array         [description]
     */
    public function getInsertedHistory(Table $table = null): array
    {
        if (false == is_null($table)) {
            return $this->history['inserted'][$table->getName()] ?? [];
        }

        return $this->history['inserted'] ?? [];
    }

    /**
     * Clear history of executed insert.
     *
     * @param  Table|null $table [description]
     * @return self          [description]
     */
    public function clearInsertedHistory(Table $table = null): self
    {
        if (false == is_null($table)) {
            unset($this->history['inserted'][$table->getName()]);
            return $this;
        }

        unset($this->history['inserted']);
        return $this;
    }

    /**
     * Returns executed statements history.
     *
     * @return array [description]
     */
    public function getStatementsHistory(): array
    {
        return $this->getConnection()->getStatementsQueue();
    }

    /**
     * Clear executed statements history.
     *
     * @return self [description]
     */
    public function clearStatementsHistory(): self
    {
        $this->getConnection()->clearStatementsQueue();
        return $this;
    }
}
