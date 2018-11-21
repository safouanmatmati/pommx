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

use Pommx\Entity\AbstractEntity;
use Pommx\Repository\AbstractRepository;

use Pommx\MapProperties\Annotation\MapValue;
use Pommx\Relation\Annotation\Relation;

use Pommx\EntityManager\Annotation\CascadeDelete;
use Pommx\EntityManager\Annotation\CascadeDeletes;
use Pommx\EntityManager\Annotation\CascadePersist;

use App\Tests\Pommx\EntityManager\Dataset\DummyPerson\DummyPersonRepository;
use App\Tests\Pommx\EntityManager\Dataset\DummyDog\DummyDog;
use App\Tests\Pommx\EntityManager\Dataset\DummyPersonDog\DummyPersonDogRepository;
use App\Tests\Pommx\EntityManager\Dataset\DummyPersonDog\DummyPersonDog;

/**
 * @CascadePersist(cascade=false)
 * @CascadeDeletes(
 *  list={
 *      {"class"=DummyPersonDog::class, "map"="person_name"}
 *  }
 * )
 */
class DummyPerson extends AbstractEntity
{
    /**
     * [public description]
     * @var string
     */
    public $name;

    /**
     *
     * @CascadeDelete(class=DummyPerson::class)
     * @MapValue(source="$father", property="name")
     */
    protected $dummy_person_father_name;

    /**
     * [private description]
     *
     * @var       self
     * @Relation(
     *  type="manyToOne",
     *  related=DummyPerson::class,
     *  property="children_father"
     * )
     */
    protected $father;

    /**
     *
     * @CascadeDelete(class=DummyPerson::class)
     * @MapValue(source="$mother", property="name")
     */
    protected $dummy_person_mother_name;

    /**
     * [private description]
     *
     * @var       self
     * @Relation(
     *  type="manyToOne",
     *  related=DummyPerson::class,
     *  property="children_mother"
     * )
     */
    protected $mother;

    /**
     * [private description]
     *
     * @var        DummyDog[]
     * @Relation(
     *  type="manyToMany",
     *  related=DummyDog::class,
     *  property="person_dog",
     *  mid={
     *      "class"=DummyPersonDog::class,
     *      "factory"={
     *          AbstractRepository::class,
     *          "staticEntityFactory", {"class"=DummyPersonDog::class}
     *      },
     *      "related_property"="dog",
     *      "current_property"="person"
     *  }
     * )
     */
    private $dogs;

    public function setFather($value)
    {
        $this->set('father', $value);
    }

    public function setMother($value)
    {
        $this->set('mother', $value);
    }

    public function addDog($value)
    {
        $this->addTo('dogs', $value);
    }

    public function removeDog($value)
    {
        $this->removeFrom('dogs', $value);
    }

    public function getDogsRelations(): array
    {
        return $this->relationGetMidRelationsFrom('dogs');
    }
}
