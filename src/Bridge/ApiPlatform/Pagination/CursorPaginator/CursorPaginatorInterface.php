<?php

/*
 * This file is part of the Pommx package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);


namespace Pommx\Bridge\ApiPlatform\Pagination\CursorPaginator;

use Pommx\Bridge\ApiPlatform\Pagination\CursorPaginator\PartialCursorPaginatorInterface;

interface CursorPaginatorInterface extends PartialCursorPaginatorInterface
{

    public function getLastCursor();
    
    public function hasNextPage(): bool;
}
