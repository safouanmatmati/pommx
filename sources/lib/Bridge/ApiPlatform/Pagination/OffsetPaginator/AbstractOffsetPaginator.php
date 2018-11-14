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


namespace PommX\Bridge\ApiPlatform\Pagination\OffsetPaginator;

use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use PommX\Repository\Extension\Pagination\OffsetPagerInterface;

abstract class AbstractOffsetPaginator implements \IteratorAggregate, PartialPaginatorInterface
{
    /**
     * [protected description]
     * @var OffsetPagerInterface
     */
    protected $pager;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(OffsetPagerInterface $pager)
    {
        $this->pager = $pager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return $this->pager->getPage();
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
}
