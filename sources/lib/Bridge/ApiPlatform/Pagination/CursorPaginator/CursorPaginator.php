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

use PommX\Bridge\ApiPlatform\Pagination\CursorPaginator\CursorPaginatorInterface;
use PommX\Bridge\ApiPlatform\Pagination\CursorPaginator\AbstractCursorPaginator;

final class CursorPaginator extends AbstractCursorPaginator implements CursorPaginatorInterface
{
    public function getLastCursor()
    {
        return $this->pager->getLastCursor();
    }

    public function hasNextPage(): bool
    {
        return $this->pager->hasNextPage();
    }
}
