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


namespace PommX\Repository\QueryBuilder\Extension\Pagination;

use PommProject\Foundation\ResultIterator;

interface CursorPagerInterface
{
    /**
     * getIterator
     *
     * Return the Pager's iterator.
     *
     * @access public
     * @return ResultIterator
     */
    public function getIterator(): ResultIterator;

    /**
     * getResultCount
     *
     * Get the number of results in this collection.
     *
     * @access public
     * @return int
     */
    public function getResultCount(): int;

    /**
     * getResultMax
     *
     * Get the cursor of the last element of this collection.
     *
     * @access public
     * @return int
     */
    public function getResultLastCursor();

    /**
     * getLastResult
     *
     * Get the last element cursor from current collection.
     *
     * @access public
     * @return int
     */
    public function getLastCursor();

    /**
     * hasNextPage
     *
     * True if a next element exists in next collection.
     *
     * @access public
     * @return Boolean
     */
    public function hasNextPage(): bool;

    /**
     * getCount
     *
     * Get the total number of results in all pages.
     *
     * @access public
     * @return int
     */
    public function getCount(): int;

    /**
     * getMaxPerPage
     *
     * Get maximum result per page.
     *
     * @access public
     * @return int
     */
    public function getMaxPerPage(): float;

    public function getCursor(int $index);

    public function generateCursor($data);
}
