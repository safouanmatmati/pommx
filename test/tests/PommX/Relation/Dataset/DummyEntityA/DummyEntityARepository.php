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

namespace App\Tests\Pommx\Relation\Dataset\DummyEntityA;

use Pommx\Repository\AbstractRepository;

use App\Tests\Pommx\Relation\Dataset\DummyEntityA\DummyEntityA;
use App\Tests\Pommx\Relation\Dataset\DummyEntityA\DummyEntityAStructure;

class DummyEntityARepository extends AbstractRepository
{
    /**
     * Repository constructor.
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct(new DummyEntityAStructure(), DummyEntityA::class);
    }
}
