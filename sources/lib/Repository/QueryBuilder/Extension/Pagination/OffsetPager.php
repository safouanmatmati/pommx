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

use PommX\Repository\QueryBuilder\Extension\Pagination\OffsetPagerInterface;

use PommProject\Foundation\ResultIterator;
use PommProject\Foundation\Pager as PommPager;

class OffsetPager extends PommPager implements OffsetPagerInterface
{
    protected $iterator;
    protected $count;
    protected $max_per_page;
    protected $offset;

    /**
     * __construct
     *
     * @access public
     * @param  ResultIterator $iterator
     * @param  int            $max_per_page Results per page
     * @param  int            $offset       Start index.
     * @param  int            $count        Total number of results.
     */
    public function __construct(ResultIterator $iterator, $max_per_page, $offset, $count = null)
    {
        $this->iterator     = $iterator;
        $this->max_per_page = $max_per_page;
        $this->offset       = $offset;
        $this->count        = $count;
    }

    /**
     * getIterator
     *
     * Return the Pager's iterator.
     *
     * @access public
     * @return ResultIterator
     */
    public function getIterator(): ResultIterator
    {
        return $this->iterator;
    }

    /**
     * getResultCount
     *
     * Get the number of results in this page.
     *
     * @access public
     * @return int
     */
    public function getResultCount(): int
    {
        return $this->iterator->count();
    }

    /**
     * getResultMin
     *
     * Get the index of the first element of this page.
     *
     * @access public
     * @return int
     */
    public function getResultMin(): int
    {
        return $this->offset;
    }

    /**
     * getResultMax
     *
     * Get the index of the last element of this page.
     *
     * @access public
     * @return int
     */
    public function getResultMax(): int
    {
        return ($this->offset + $this->getResultCount())-1;
    }

    /**
     * getLastResult
     *
     * Get the last element index.
     *
     * @access public
     * @return int
     */
    public function getLastResult(): int
    {
        return $this->count-1;
    }

    /**
     * isNextPage
     *
     * True if a next element exists.
     *
     * @access public
     * @return bool
     */
    public function isNextPage(): bool
    {
        return (bool) ($this->getResultMax() < $this->getLastResult());
    }

    /**
     * isPreviousPage
     *
     * True if a previous page exists.
     *
     * @access public
     * @return bool
     */
    public function isPreviousPage(): bool
    {
        return (bool) ($this->getResultMin() > 0);
    }

    /**
     * getCount
     *
     * Get the total number of results in all pages.
     *
     * @access public
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * getMaxPerPage
     *
     * Get maximum result per page.
     *
     * @access public
     * @return int
     */
    public function getMaxPerPage(): float
    {
        return (float) $this->max_per_page;
    }

    public function getLastPage(): float
    {
        return (float) ($this->getCount() === 0 ? 1 : ceil(($this->getCount()/$this->getMaxPerPage())));
    }

    public function getPage(): float
    {
        return (float) (ceil((($this->offset+1)/$this->getMaxPerPage())));
    }
}
