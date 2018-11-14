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

namespace App\Tests\PommX\Fetch\Dataset\DummyPersonDog;

use PommX\Entity\AbstractEntity;
use PommX\MapProperties\Annotation\MapValue;
use PommX\Fetch\Annotation\CascadeDelete;

use App\Tests\PommX\Fetch\Dataset\DummyPersonDog\DummyPersonDogRepository;
use App\Tests\PommX\Fetch\Dataset\DummyDog\DummyDog;
use App\Tests\PommX\Fetch\Dataset\DummyPerson\DummyPerson;

class DummyPersonDog extends AbstractEntity
{

}
