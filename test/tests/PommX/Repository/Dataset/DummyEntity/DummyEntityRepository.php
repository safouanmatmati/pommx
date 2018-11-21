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

namespace App\Tests\Pommx\Repository\Dataset\DummyEntity;

use Pommx\Repository\AbstractRepository;

use App\Tests\Pommx\Repository\Dataset\DummyEntity\DummyEntity;
use App\Tests\Pommx\Repository\Dataset\DummyEntity\DummyEntityStructure;

use App\Tests\TestTools\RepositoryTestTools;

class DummyEntityRepository extends AbstractRepository
{
    use RepositoryTestTools;

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
