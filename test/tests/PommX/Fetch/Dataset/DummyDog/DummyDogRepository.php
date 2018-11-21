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

namespace App\Tests\Pommx\Fetch\Dataset\DummyDog;

use Pommx\Repository\AbstractRepository;

use App\Tests\Pommx\Fetch\Dataset\DummyDog\DummyDog;
use App\Tests\Pommx\Fetch\Dataset\DummyDog\DummyDogStructure;

use App\Tests\Pommx\Fetch\Dataset\RepositoryTestTools;

class DummyDogRepository extends AbstractRepository
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
            new DummyDogStructure(),
            DummyDog::class
        );
    }
}
