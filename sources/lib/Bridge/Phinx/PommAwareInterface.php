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

namespace PommX\Bridge\Phinx;

use PommProject\Foundation\Pomm;

interface PommAwareInterface
{
    public function setPomm(Pomm $pomm);
}
