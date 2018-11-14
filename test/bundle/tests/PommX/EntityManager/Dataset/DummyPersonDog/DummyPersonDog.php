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

namespace App\Tests\PommX\EntityManager\Dataset\DummyPersonDog;

use PommX\Entity\AbstractEntity;
use PommX\MapProperties\Annotation\MapValue;
use PommX\EntityManager\Annotation\CascadeDelete;

use App\Tests\PommX\EntityManager\Dataset\DummyPersonDog\DummyPersonDogRepository;
use App\Tests\PommX\EntityManager\Dataset\DummyDog\DummyDog;
use App\Tests\PommX\EntityManager\Dataset\DummyPerson\DummyPerson;

class DummyPersonDog extends AbstractEntity
{
    /**
     * [public description]
     *
     * @var string
     * @CascadeDelete(class=DummyPerson::class)
     * @MapValue(source="$person", property="name")
     */
    public $person_name;

    /**
     * [public description]
     *
     * @var string
     */
    public $person;

    /**
     * [public description]
     *
     * @var string
     * @CascadeDelete(class=DummyDog::class)
     * @MapValue(source="$dog", property="name")
     */
    public $dog_name;

    /**
     * [public description]
     *
     * @var string
     */
    public $dog;
}
