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

namespace App\Tests\Pommx\EntityManager\Dataset\DummyPerson;

use Pommx\Repository\AbstractRepository;

use App\Tests\Pommx\EntityManager\Dataset\DummyPerson\DummyPerson;
use App\Tests\Pommx\EntityManager\Dataset\DummyPerson\DummyPersonStructure;

use App\Tests\Pommx\EntityManager\Dataset\RepositoryTestTools;

class DummyPersonRepository extends AbstractRepository
{
    use RepositoryTestTools;

    /**
     * Repository constructor.
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct(
            new DummyPersonStructure(),
            DummyPerson::class
        );
    }
}
