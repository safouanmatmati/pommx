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

namespace Pommx\Repository\QueryBuilder\Extension\Pagination;

use PommProject\Foundation\ResultIterator;

interface OffsetPagerInterface
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
     * Get the number of results in this page.
     *
     * @access public
     * @return int
     */
    public function getResultCount(): int;
    /**
     * getResultMin
     *
     * Get the index of the first element of this page.
     *
     * @access public
     * @return int
     */
    public function getResultMin(): int;

    /**
     * getResultMax
     *
     * Get the index of the last element of this page.
     *
     * @access public
     * @return int
     */
    public function getResultMax(): int;
    /**
     * getLastResult
     *
     * Get the last element index.
     *
     * @access public
     * @return int
     */
    public function getLastResult(): int;
    /**
     * isNextPage
     *
     * True if a next element exists.
     *
     * @access public
     * @return bool
     */
    public function isNextPage(): bool;

    /**
     * isPreviousPage
     *
     * True if a previous page exists.
     *
     * @access public
     * @return bool
     */
    public function isPreviousPage(): bool;

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

    public function getLastPage(): float;

    public function getPage(): float;
}
