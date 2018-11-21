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

namespace App\Tests\Pommx\Fetch\Dataset\DummyPersonDog;

use Pommx\Entity\AbstractEntity;
use Pommx\MapProperties\Annotation\MapValue;
use Pommx\Fetch\Annotation\CascadeDelete;

use App\Tests\Pommx\Fetch\Dataset\DummyPersonDog\DummyPersonDogRepository;
use App\Tests\Pommx\Fetch\Dataset\DummyDog\DummyDog;
use App\Tests\Pommx\Fetch\Dataset\DummyPerson\DummyPerson;

class DummyPersonDog extends AbstractEntity
{

}
