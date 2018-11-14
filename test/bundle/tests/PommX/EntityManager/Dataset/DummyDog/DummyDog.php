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

namespace App\Tests\PommX\EntityManager\Dataset\DummyDog;

use PommX\Entity\AbstractEntity;
use PommX\EntityManager\Annotation\CascadeDeletes;

use App\Tests\PommX\EntityManager\Dataset\DummyPersonDog\DummyPersonDog;
use App\Tests\PommX\EntityManager\Dataset\DummyDog\DummyDogRepository;

/**
 *
 * @CascadeDeletes(
 *  list={
 *      {"class"=DummyPersonDog::class, "map"="dog_name"}
 *  }
 * )
 */
class DummyDog extends AbstractEntity
{
    /**
     * [public description]
     *
     * @var string
     */
    public $name;

    public function getRelations(): array
    {
        return $this->relationGetMidRelationsFrom('person_dog');
    }
}
