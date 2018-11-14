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

namespace App\Tests\PommX\MapProperties\Dataset;

use PommX\Repository\AbstractRepository;

use App\Tests\PommX\MapProperties\Dataset\DummyEntity;
use App\Tests\PommX\MapProperties\Dataset\DummyEntityStructure;

class DummyEntityRepository extends AbstractRepository
{
    /**
     * Repository constructor.
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct(new DummyEntityStructure(), DummyEntity::class);
    }
}
