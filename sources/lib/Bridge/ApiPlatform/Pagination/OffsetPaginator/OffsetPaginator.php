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

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use PommX\Bridge\ApiPlatform\Pagination\OffsetPaginator\AbstractOffsetPaginator;

final class OffsetPaginator extends AbstractOffsetPaginator implements PaginatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        return (float) $this->pager->getLastPage();
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return (float) $this->pager->getCount();
    }
}
