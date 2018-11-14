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

interface PartialCursorPaginatorInterface extends \Traversable, \Countable
{
    /**
     * Gets the number of items by page.
     *
     * @return float
     */
    public function getItemsPerPage(): float;

    public function getCursor(int $index);
}
