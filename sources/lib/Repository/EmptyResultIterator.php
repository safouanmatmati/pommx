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

namespace PommX\Repository;

use PommProject\Foundation\ResultIterator;
use PommProject\Foundation\Session\Session;

final class EmptyResultIterator
{
    /**
     * @var ResultIterator
     */
    private static $instance;

    public static function getInstance(Session $session): ResultIterator
    {
        if (true == isset(self::$instance)) {
            return self::$instance;
        }

        $result = $session
            ->getClientUsingPooler('prepared_query', 'SELECT false WHERE 1=2;')
            ->execute();

         self::$instance = new ResultIterator($result);

        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
