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

namespace App\Tests\PommX\Fetch\Dataset\DummyPerson;

use PommX\Entity\AbstractEntity;

use PommX\Fetch\Annotation\Fetch;

use App\Tests\PommX\Fetch\Dataset\DummyPerson\DummyPersonRepository;
use App\Tests\PommX\Fetch\Dataset\DummyDog\DummyDog;
use App\Tests\PommX\Fetch\Dataset\DummyPersonDog\DummyPersonDog;

class DummyPerson extends AbstractEntity
{
    /**
     *
     * @Fetch(
     *  Fetch::MODE_PROXY,
     *  to=DummyPerson::class,
     *  map={"dummy_person_father_name"}
     * )
     */
    public $proxy_father;

    /**
     *
     * @Fetch(
     *  Fetch::MODE_PROXY,
     *  to=DummyPerson::class,
     *  map={"dummy_person_mother_name"},
     *  values={"nickname"="mommy"}
     * )
     */
    public $proxy_mother;

    /**
     *
     * @Fetch(Fetch::MODE_LAZY)
     */
    public $nickname;

    /**
     *
     * @Fetch(Fetch::MODE_LAZY)
     */
    public $dummy_person_father_name;

    /**
     *
     * @Fetch(Fetch::MODE_LAZY)
     */
    public $dummy_person_mother_name;

    /**
     *
     * @Fetch(Fetch::MODE_LAZY, from=DummyPersonDog::class, map={"person_name"})
     */
    public $lazy_dogs_relations;

    /**
     *
     * @Fetch(
     *  Fetch::MODE_LAZY,
     *  callback={DummyPersonRepository::class, "fetchDogsRelations"}
     * )
     */
    public $lazy_dogs_relations_from_callback;

    /**
     *
     * @Fetch(Fetch::MODE_EXTRA_LAZY, to=DummyPerson::class, map={"dummy_person_mother_name"})
     */
    public $extra_lazy_mother;

    /**
     *
     * @Fetch(Fetch::MODE_EXTRA_LAZY, from=DummyPerson::class, map={"id_parent"})
     */
    public $extra_lazy_children;

    /**
     *
     * @Fetch(
     *  Fetch::MODE_JOIN,
     *  map={"person_name"},
     *  join={DummyPersonDog::class=DummyDog::class}
     * )
     */
    public $dogs;

    /**
     *
     * @Fetch(
     *  Fetch::MODE_JOIN,
     *  callback={DummyPersonRepository::class, "fetchDogsJoin", "args"={"custom_args"=DummyDog::class}}
     * )
     */
    public $dogs_from_callback;

    public function getLazyDogsRelations()
    {
        return $this->get('lazy_dogs_relations');
    }

    public function getExtraLazyMother()
    {
        return $this->get('extra_lazy_mother');
    }
}
