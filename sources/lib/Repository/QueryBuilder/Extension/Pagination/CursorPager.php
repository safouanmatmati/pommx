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

use PommX\Repository\QueryBuilder\Extension\Pagination\PagerInterface;

use PommProject\Foundation\ResultIterator;

final class CursorPager implements PagerInterface
{
    private $iterator;
    private $max_per_page;
    private $cursor_generator;
    private $last_data;
    private $last_cursor;

    /**
     * __construct
     *
     * @access public
     * @param  ResultIterator $iterator
     * @param  int            $max_per_page     Results per page
     * @param  callable       $cursor_generator cursor generator
     * @param  mixed|null     $last_data        Last result
     */
    public function __construct(ResultIterator $iterator, $max_per_page, callable $cursor_generator, $last_data = null)
    {
        $this->iterator     = $iterator;
        $this->max_per_page = $max_per_page;
        $this->cursor_generator = $cursor_generator;
        $this->last_data = $last_data;
    }

    /**
     * getIterator
     *
     * Return the Pager's iterator.
     *
     * @access public
     * @return ResultIterator
     */
    public function getIterator()
    {
        return $this->iterator;
    }

    /**
     * getResultCount
     *
     * Get the number of results in this collection.
     *
     * @access public
     * @return int
     */
    public function getResultCount()
    {
        return $this->iterator->count();
    }

    /**
     * getResultMax
     *
     * Get the cursor of the last element of this collection.
     *
     * @access public
     * @return int
     */
    public function getResultLastCursor()
    {
        if (0 > ($index = $this->getResultCount()-1)) {
            return null;
        }
        $data = $this->getIterator()->get($index);

        return $this->generateCursor($data);
    }

    /**
     * getLastResult
     *
     * Get the last element cursor from current collection.
     *
     * @access public
     * @return int
     */
    public function getLastCursor()
    {
        if (false == is_null($this->last_cursor)) {
            return $this->last_cursor;
        }

        return $this->last_cursor = $this->generateCursor($this->last_data);
    }

    /**
     * hasNextPage
     *
     * True if a next element exists in next collection.
     *
     * @access public
     * @return Boolean
     */
    public function hasNextPage(): bool
    {
        return (bool) ($this->getResultLastCursor() != $this->getLastCursor());
    }

    /**
     * getCount
     *
     * Get the total number of results in all pages.
     *
     * @access public
     * @return int
     */
    public function getCount()
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

    public function getCursor(int $index)
    {
        $data = $this->getIterator()->get($index);
        return $this->generateCursor($data);
    }

    public function generateCursor($data)
    {
        $generator = $this->cursor_generator;

        if (true == is_null($data)) {
            return null;
        }
        return $generator($data);
    }
}
