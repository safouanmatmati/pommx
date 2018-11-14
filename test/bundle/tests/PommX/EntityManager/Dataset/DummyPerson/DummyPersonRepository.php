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

namespace App\Tests\PommX\EntityManager\Dataset\DummyPerson;

use PommX\Repository\AbstractRepository;

use App\Tests\PommX\EntityManager\Dataset\DummyPerson\DummyPerson;
use App\Tests\PommX\EntityManager\Dataset\DummyPerson\DummyPersonStructure;

use App\Tests\PommX\EntityManager\Dataset\RepositoryTestTools;

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
