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

namespace PommX\Repository\Layer;

use PommProject\ModelManager\ModelLayer\ModelLayer;

abstract class GlobalTransaction extends ModelLayer
{
    private static $transaction_identifier;

    public function initDefaultTransaction(): ?int
    {
        if (true == $this->isInTransaction()) {
            return null;
        }

        self::$transaction_identifier = rand();

        $this->startTransaction();

        return self::$transaction_identifier;
    }

    public function commitDefaultTransaction(int $transaction_identifier = null): bool
    {
        if (false == $this->isInTransaction()) {
            return false;
        }

        if (self::$transaction_identifier === $transaction_identifier) {
            $this->commitTransaction();

            return true;
        }

        return false;
    }

    public function setDefaultDeferrable(array $indexes, $state): self
    {
        return $this->setDeferrable($indexes, $state);
    }
}
