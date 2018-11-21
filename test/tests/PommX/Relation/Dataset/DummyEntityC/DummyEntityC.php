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

namespace App\Tests\Pommx\Relation\Dataset\DummyEntityC;

use Pommx\Relation\Annotation\Relation;
use Pommx\Entity\AbstractEntity;

use App\Tests\Pommx\Relation\Dataset\DummyEntityA\DummyEntityA;

class DummyEntityC extends AbstractEntity
{
    /**
     * [private description]
     *
     * @var       DummyEntityA
     * @Relation(
     *  related=DummyEntityA::class,
     *  property="one_to_one_dummy_entity_c",
     *  setter={DummyEntityC::class, "setter"},
     *  getter={DummyEntityC::class, "getter"}
     * )
     */
    private $one_to_one_dummy_entity_a;

    /**
     * [private description]
     *
     * @var       DummyEntityA
     * @Relation(
     *  related=DummyEntityA::class,
     *  property="one_to_many_dummy_entities_c"
     * )
     */
    private $many_to_one_dummy_entity_a;

    /**
     * [private description]
     *
     * @var       DummyEntityA[]
     * @Relation(
     *  related=DummyEntityA::class,
     *  property="many_to_one_dummy_entity_c"
     * )
     */
    private $one_to_many_dummy_entities_a;

    /**
     * [private description]
     *
     * @var       DummyEntityA[]
     * @Relation(
     *  related=DummyEntityA::class,
     *  property="many_to_many_dummy_entities_c",
     *  mid={
     *      "property"="mid_many_to_many_entities_ca_c",
     *      "class"="stdClass",
     *      "factory"={DummyEntityC::class, "midFactory"},
     *      "setter"={DummyEntityC::class, "midSetter"},
     *      "getter"={DummyEntityC::class, "midGetter"},
     *      "related_property"="dummy_entity_a",
     *      "current_property"="dummy_entity_c"
     *  }
     * )
     */
    private $many_to_many_dummy_entities_a;

    public function getOneToOneRelatedEntity()
    {
        return $this->get('one_to_one_dummy_entity_a');
    }

    public function setOneToOneRelatedEntity($value = null)
    {
        $this->set('one_to_one_dummy_entity_a', $value);
    }


    public function getManyToManyRelatedEntities()
    {
        return $this->get('many_to_many_dummy_entities_a');
    }

    public function addManyToManyRelatedEntity($value)
    {
        $this->addTo('many_to_many_dummy_entities_a', $value);
    }

    public function removeManyToManyRelatedEntity($value)
    {
        $this->removeFrom('many_to_many_dummy_entities_a', $value);
    }

    public function getManyToManyMidRelations()
    {
        return $this->get('many_to_many_dummy_entities_a');
    }

    public function getManyToManyMidRelationFrom($value)
    {
        return $this->relationGetMidRelationWith('many_to_many_dummy_entities_a', $value);
    }

    public static function setter($entity, $var, $value)
    {
        $entity->setter = $entity->setter ?? [];
        $entity->setter['one_to_one_dummy_entity_a'] = true;

        $entity->{$var} = $value;

        return $entity->{$var};
    }

    public static function getter($entity, $var)
    {
        $entity->getter = $entity->getter ?? [];
        $entity->getter['one_to_one_dummy_entity_a'] = true;

        return $entity->{$var};
    }

    public static function midFactory($parameters)
    {
        $object = new \stdClass();
        $object->parameters = $parameters;
        return $object;
    }

    public static function midSetter($entity, $var, $value)
    {
        $entity->setter = $entity->setter ?? [];
        $entity->setter['many_to_many_dummy_setter'] = true;

        $entity->{$var} = $value;

        return $entity->{$var};
    }

    public static function midGetter($entity, $var)
    {
        $entity->getter = $entity->getter ?? [];
        $entity->getter['many_to_many_dummy_getter'] = true;

        return $entity->{$var};
    }
}
