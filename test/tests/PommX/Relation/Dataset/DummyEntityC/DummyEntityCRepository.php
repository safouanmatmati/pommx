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

namespace App\Tests\Pommx\Relation\Dataset\DummyEntityC;

use Pommx\Repository\AbstractRepository;

use App\Tests\Pommx\Relation\Dataset\DummyEntityC\DummyEntityC;
use App\Tests\Pommx\Relation\Dataset\DummyEntityC\DummyEntityCStructure;

class DummyEntityCRepository extends AbstractRepository
{
    /**
     * Repository constructor.
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct(new DummyEntityCStructure(), DummyEntityC::class);
    }
}
