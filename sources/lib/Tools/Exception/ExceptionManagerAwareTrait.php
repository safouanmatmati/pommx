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

namespace PommX\Tools\Exception;

use PommX\Tools\Exception\ExceptionManagerInterface;

trait ExceptionManagerAwareTrait
{
    /**
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    /**
     * Defines exception manager.
     *
     * @param ExceptionManagerInterface $exception_manager
     */
    public function setExceptionManager(ExceptionManagerInterface $exception_manager)
    {
        $this->exception_manager = $exception_manager;
    }

    /**
     * Returns exception manager.
     *
     * @param ExceptionManagerInterface $exception_manager
     */
    public function getExceptionManager(): ExceptionManagerInterface
    {
        return $this->exception_manager;
    }
}
