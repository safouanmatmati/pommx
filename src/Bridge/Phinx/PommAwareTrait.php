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

namespace Pommx\Bridge\Phinx;

use PommProject\Foundation\Pomm;

trait PommAwareTrait
{
    /**
     * [$pomm description]
     *
     * @var Pomm
     */
    public $pomm;

    /**
     * {@inheritdoc}
     */
    public function setPomm(Pomm $pomm)
    {
        $this->pomm = $pomm;
    }
}
