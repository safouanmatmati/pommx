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

use \PDO as PhpPDO;

class PDO extends PhpPDO
{
    /**
     * [private description]
     *
     * @var string[]
     */
    private $statements_queue = [];

    public function exec($statement)
    {
        $this->addToStatementsQueue($statement);

        $nb_affected = parent::exec($statement);

        $this->addToStatementsQueue(sprintf('%d affected rows', $nb_affected));

        return $nb_affected;
    }

    public function query($statement, ... $args)
    {
        $this->addToStatementsQueue($statement);

        $result = parent::query($statement, ... $args);

        return $result;
    }

    public function prepare($statement, $driver_options = [])
    {
        $this->addToStatementsQueue($statement);

        $result = parent::prepare($statement, $driver_options);

        return $result;
    }

    private function addToStatementsQueue(string $message): self
    {
        $this->statements_queue[] = $message;
        return $this;
    }

    public function getStatementsQueue(): array
    {
        return $this->statements_queue;
    }

    public function clearStatementsQueue(): self
    {
        $this->statements_queue = [];
        return $this;
    }
}
