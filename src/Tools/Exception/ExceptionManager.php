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

use Pommx\Tools\Exception\ExceptionManagerInterface;

class ExceptionManager implements ExceptionManagerInterface
{
    /**
     * Throw & format exception
     *
     * @param  string          $class
     * @param  int|string      $line
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
    ): void {
        $message = sprintf(
            '%s Exception at line %s :'.PHP_EOL.PHP_EOL
            .'%s',
            $class,
            $line,
            $message
        );

        if (false == is_null($prev_exception)) {
            $message = sprintf(
                '%s'.PHP_EOL.PHP_EOL
                .'- Previous exception '.PHP_EOL
                .'File "%s" at line "%s" :'.PHP_EOL
                .'%s',
                $message,
                $prev_exception->getFile(),
                $prev_exception->getLine(),
                $prev_exception->getMessage()
            );
        }

        $exception = $class_exception ?? \LogicException::class;

        throw new $exception($message, 0, $prev_exception);
    }
}
