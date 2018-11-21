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

namespace Pommx\Tools\Exception;

interface ExceptionManagerInterface
{
    /**
     * Throw & format exception
     *
     * @param  string          $class
     * @param  int             $line
     * @param  string          $message
     * @param  string          $class_exception
     * @param  \Throwable|null $prev_exception
     * @throws \Throwable
     */
    public static function throw(
        string $class,
        int $line,
        string $message,
        string $class_exception = null,
        \Throwable $prev_exception = null
    ): void;
}
