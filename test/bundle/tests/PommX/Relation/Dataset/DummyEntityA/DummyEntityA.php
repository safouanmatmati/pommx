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

namespace App\Tests\PommX\Relation\Dataset\DummyEntityA;

use PommX\Relation\Annotation\Relation;
use PommX\Entity\AbstractEntity;

use App\Tests\PommX\Relation\Dataset\DummyEntityB\DummyEntityB;
use App\Tests\PommX\Relation\Dataset\DummyEntityC\DummyEntityC;

class DummyEntityA extends AbstractEntity
{
    /**
     * [private description]
     *
     * @var        DummyEntityB
     * @Relation(
     *  type="oneToOne",
     *  related={
     *      "class"=DummyEntityB::class,
     *      "property"="one_to_one_dummy_entity_a"
     *  }
     * )
     */
    private $one_to_one_dummy_entity_b;

    /**
     * [private description]
     *
     * @var        DummyEntityB
     * @Relation(
     *  type="manyToOne",
     *  related=DummyEntityB::class,
     *  property="one_to_many_dummy_entities_a"
     * )
     */
    private $many_to_one_dummy_entity_b;

    /**
     * [private description]
     *
     * @var        DummyEntityB[]
     * @Relation(
     *  type="oneToMany",
     *  related=DummyEntityB::class,
     *  property="many_to_one_dummy_entity_a"
     * )
     */
    private $one_to_many_dummy_entities_b;

    /**
     * [private description]
     *
     * @var        DummyEntityB[]
     * @Relation(
     *  type="manyToMany",
     *  related={
     *      "class"=DummyEntityB::class,
     *      "property"="many_to_many_dummy_entities_a"
     *  }
     * )
     */
    private $many_to_many_dummy_entities_b;

    /**
     * [private description]
     *
     * @var        DummyEntityC
     * @Relation(
     *  type="oneToOne",
     *  setter={DummyEntityA::class, "setter"},
     *  getter={DummyEntityA::class, "getter"},
     *  related={
     *      "class"=DummyEntityC::class,
     *      "property" ="one_to_one_dummy_entity_a"
     *  }
     * )
     */
    private $one_to_one_dummy_entity_c;

    /**
     * [private description]
     *
     * @var        DummyEntityC
     * @Relation(
     *  type="manyToOne",
     *  related=DummyEntityC::class,
     *  property="one_to_many_dummy_entities_a"
     * )
     */
    private $many_to_one_dummy_entity_c;

    /**
     * [private description]
     *
     * @var        DummyEntityC[]
     * @Relation(
     *  type="oneToMany",
     *  related=DummyEntityC::class,
     *  property="many_to_one_dummy_entity_a"
     * )
     */
    private $one_to_many_dummy_entities_c;

    /**
     * [private description]
     *
     * @var        DummyEntityC[]
     * @Relation(
     *  type="manyToMany",
     *  related=DummyEntityC::class,
     *  property="many_to_many_dummy_entities_a",
     *  mid={
     *      "property"="mid_many_to_many_entities_ac_a"
     *  }
     * )
     */
    private $many_to_many_dummy_entities_c;

    public function getOneToOneRelatedEntity()
    {
        return $this->get('one_to_one_dummy_entity_b');
    }

    public function setOneToOneRelatedEntity($value = null)
    {
        $this->set('one_to_one_dummy_entity_b', $value);
    }

    /**
     * [addOneToManyRelatedEntity description]
     *
     * @param [type] $value [description]
     */
    public function getManyToOneRelatedEntity()
    {
        return $this->get('many_to_one_dummy_entity_b');
    }

    public function setManyToOneRelatedEntity($value = null)
    {
        $this->set('many_to_one_dummy_entity_b', $value);
    }

    /**
     * [getOneToManyRelatedEntities description]
     *
     * @return [type] [description]
     */
    public function getOneToManyRelatedEntities()
    {
        return $this->get('one_to_many_dummy_entities_b');
    }

    public function addOneToManyRelatedEntity($value)
    {
        $this->addTo('one_to_many_dummy_entities_b', $value);
    }

    public function removeOneToManyRelatedEntity($value)
    {
        $this->removeFrom('one_to_many_dummy_entities_b', $value);
    }

    /**
     * [getOneToManyRelatedEntities description]
     *
     * @return [type] [description]
     */
    public function getManyToManyRelatedEntities()
    {
        return $this->get('many_to_many_dummy_entities_b');
    }

    public function addManyToManyRelatedEntity($value)
    {
        $this->addTo('many_to_many_dummy_entities_b', $value);
    }

    public function removeManyToManyRelatedEntity($value)
    {
        $this->removeFrom('many_to_many_dummy_entities_b', $value);
    }

    public static function setter($entity, $var, $value)
    {
        $entity->setter = $entity->setter ?? [];
        $entity->setter['one_to_one_dummy_entity_c'] = true;

        $entity->{$var} = $value;

        return $entity->{$var};
    }

    public static function getter($entity, $var)
    {
        $entity->getter = $entity->getter ?? [];
        $entity->getter['one_to_one_dummy_entity_c'] = true;

        return $entity->{$var};
    }
}
