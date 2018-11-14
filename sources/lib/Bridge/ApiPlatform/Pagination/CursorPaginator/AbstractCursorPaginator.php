<?php

/*
 * This file is part of the PommX package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PommX\Bridge\ApiPlatform\Pagination\CursorPaginator;

use PommX\Bridge\ApiPlatform\Pagination\CursorPaginator\PartialCursorPaginatorInterface;
use PommX\Repository\Extension\Pagination\CursorPagerInterface;

abstract class AbstractCursorPaginator implements \IteratorAggregate, PartialCursorPaginatorInterface
{
    /**
     * @var CursorPagerInterface
     */
    protected $pager;

    /**
     * @param CursorPagerInterface $pager
     */
    public function __construct(CursorPagerInterface $pager)
    {
        $this->pager = $pager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float) $this->pager->getMaxPerPage();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->pager->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->pager->getIterator()->count();
    }

    public function getCursor(int $index)
    {
        return $this->pager->getCursor($index);
    }
}
