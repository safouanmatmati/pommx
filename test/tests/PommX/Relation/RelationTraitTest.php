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

namespace App\Tests\Pommx\Relation;

use Pommx\Tools\InheritedReflection\InheritedReflectionClass;
use App\Tests\AbstractTestCase;

use App\Tests\Pommx\Relation\Dataset\DummyEntityA\DummyEntityA;
use App\Tests\Pommx\Relation\Dataset\DummyEntityA\DummyEntityARepository;

use App\Tests\Pommx\Relation\Dataset\DummyEntityB\DummyEntityB;
use App\Tests\Pommx\Relation\Dataset\DummyEntityB\DummyEntityBRepository;

use App\Tests\Pommx\Relation\Dataset\DummyEntityC\DummyEntityC;
use App\Tests\Pommx\Relation\Dataset\DummyEntityC\DummyEntityCRepository;

use App\Tests\Pommx\Relation\Dataset\DummyEntityD\DummyEntityD;
use App\Tests\Pommx\Relation\Dataset\DummyEntityD\DummyEntityDRepository;

use App\Tests\Pommx\Repository\DummyRepository;

class RelationTraitTest extends AbstractTestCase
{
    public function testDummyEntityA()
    {
        $entity_a = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityARepository::class)
            ->createEntity();

        $this->assertInstanceOf(DummyEntityA::class, $entity_a);

        return $entity_a;
    }

    public function testDummyEntityB()
    {
        $entity_b = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityBRepository::class)
            ->createEntity();

        $this->assertInstanceOf(DummyEntityB::class, $entity_b);

        return $entity_b;
    }

    /**
     *
     * @depends testDummyEntityA
     * @depends testDummyEntityB
     */
    public function testOneToOne(DummyEntityA $entity_a, DummyEntityB $entity_b)
    {
        $this->assertNull($entity_a->getOneToOneRelatedEntity());
        $this->assertNull($entity_b->getOneToOneRelatedEntity());

        $entity_a->setOneToOneRelatedEntity($entity_b);

        $this->assertEquals($entity_b, $entity_a->getOneToOneRelatedEntity());
        $this->assertEquals($entity_a, $entity_b->getOneToOneRelatedEntity());

        $entity_a->setOneToOneRelatedEntity(null);

        $this->assertNull($entity_a->getOneToOneRelatedEntity());
        $this->assertNull($entity_b->getOneToOneRelatedEntity());

        $entity_b->setOneToOneRelatedEntity($entity_a);

        $this->assertEquals($entity_b, $entity_a->getOneToOneRelatedEntity());
        $this->assertEquals($entity_a, $entity_b->getOneToOneRelatedEntity());

        $entity_b->setOneToOneRelatedEntity(null);

        $this->assertNull($entity_b->getOneToOneRelatedEntity());
        $this->assertNull($entity_a->getOneToOneRelatedEntity());
    }

    /**
     *
     * @depends testDummyEntityA
     * @depends testDummyEntityB
     */
    public function testManyToOne(DummyEntityA $entity_a, DummyEntityB $entity_b)
    {
        $entity_aa = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityARepository::class)
            ->createEntity();

        $this->assertNull($entity_a->getManyToOneRelatedEntity());
        $this->assertNull($entity_aa->getManyToOneRelatedEntity());
        $this->assertCount(0, $entity_b->getOneToManyRelatedEntities());

        $entity_a->setManyToOneRelatedEntity($entity_b);
        $entity_aa->setManyToOneRelatedEntity($entity_b);

        $this->assertEquals($entity_b, $entity_a->getManyToOneRelatedEntity());
        $this->assertEquals($entity_b, $entity_aa->getManyToOneRelatedEntity());
        $this->assertCount(2, $entity_b->getOneToManyRelatedEntities());

        $entity_a->setManyToOneRelatedEntity(null);
        $entity_aa->setManyToOneRelatedEntity(null);

        $this->assertNull($entity_a->getManyToOneRelatedEntity());
        $this->assertNull($entity_aa->getManyToOneRelatedEntity());
        $this->assertCount(0, $entity_b->getOneToManyRelatedEntities());

        $entity_b->addOneToManyRelatedEntity($entity_a);
        $entity_b->addOneToManyRelatedEntity($entity_aa);

        $this->assertEquals($entity_b, $entity_a->getManyToOneRelatedEntity());
        $this->assertEquals($entity_b, $entity_aa->getManyToOneRelatedEntity());
        $this->assertCount(2, $entity_b->getOneToManyRelatedEntities());

        $entity_b->removeOneToManyRelatedEntity($entity_a);
        $entity_b->removeOneToManyRelatedEntity($entity_aa);

        $this->assertNull($entity_a->getManyToOneRelatedEntity());
        $this->assertNull($entity_aa->getManyToOneRelatedEntity());
        $this->assertCount(0, $entity_b->getOneToManyRelatedEntities());
    }

    /**
     *
     * @depends testDummyEntityA
     * @depends testDummyEntityB
     */
    public function testOneToMany(DummyEntityA $entity_a, DummyEntityB $entity_b)
    {
        $entity_bb = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityBRepository::class)
            ->createEntity();

        $this->assertCount(0, $entity_a->getOneToManyRelatedEntities());
        $this->assertNull($entity_b->getManyToOneRelatedEntity());
        $this->assertNull($entity_bb->getManyToOneRelatedEntity());

        $entity_a->addOneToManyRelatedEntity($entity_b);
        $entity_a->addOneToManyRelatedEntity($entity_bb);

        $this->assertCount(2, $entity_a->getOneToManyRelatedEntities());
        $this->assertEquals($entity_a, $entity_b->getManyToOneRelatedEntity());
        $this->assertEquals($entity_a, $entity_bb->getManyToOneRelatedEntity());

        $entity_a->removeOneToManyRelatedEntity($entity_b);
        $entity_a->removeOneToManyRelatedEntity($entity_bb);

        $this->assertCount(0, $entity_a->getOneToManyRelatedEntities());
        $this->assertNull($entity_b->getManyToOneRelatedEntity());
        $this->assertNull($entity_bb->getManyToOneRelatedEntity());

        $entity_b->setManyToOneRelatedEntity($entity_a);
        $entity_bb->setManyToOneRelatedEntity($entity_a);

        $this->assertCount(2, $entity_a->getOneToManyRelatedEntities());
        $this->assertEquals($entity_a, $entity_b->getManyToOneRelatedEntity());
        $this->assertEquals($entity_a, $entity_bb->getManyToOneRelatedEntity());

        $entity_b->setManyToOneRelatedEntity(null);
        $entity_bb->setManyToOneRelatedEntity(null);

        $this->assertCount(0, $entity_a->getOneToManyRelatedEntities());
        $this->assertNull($entity_b->getManyToOneRelatedEntity());
        $this->assertNull($entity_bb->getManyToOneRelatedEntity());
    }

    /**
     *
     * @depends testDummyEntityA
     * @depends testDummyEntityB
     */
    public function testManyToMany(DummyEntityA $entity_a, DummyEntityB $entity_b)
    {
        $entity_aa = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityARepository::class)
            ->createEntity();

        $entity_bb = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityBRepository::class)
            ->createEntity();

        $this->assertCount(0, $entity_a->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_aa->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_b->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_bb->getManyToManyRelatedEntities());

        $entity_a->addManyToManyRelatedEntity($entity_b);
        $entity_a->addManyToManyRelatedEntity($entity_bb);
        $entity_aa->addManyToManyRelatedEntity($entity_b);
        $entity_aa->addManyToManyRelatedEntity($entity_bb);

        $this->assertCount(2, $entity_a->getManyToManyRelatedEntities());
        $this->assertCount(2, $entity_aa->getManyToManyRelatedEntities());
        $this->assertCount(2, $entity_b->getManyToManyRelatedEntities());
        $this->assertCount(2, $entity_bb->getManyToManyRelatedEntities());

        $entity_a->removeManyToManyRelatedEntity($entity_b);
        $entity_a->removeManyToManyRelatedEntity($entity_bb);
        $entity_aa->removeManyToManyRelatedEntity($entity_b);
        $entity_aa->removeManyToManyRelatedEntity($entity_bb);

        $this->assertCount(0, $entity_a->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_aa->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_b->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_bb->getManyToManyRelatedEntities());

        $entity_b->addManyToManyRelatedEntity($entity_a);
        $entity_b->addManyToManyRelatedEntity($entity_aa);
        $entity_bb->addManyToManyRelatedEntity($entity_a);
        $entity_bb->addManyToManyRelatedEntity($entity_aa);

        $this->assertCount(2, $entity_a->getManyToManyRelatedEntities());
        $this->assertCount(2, $entity_aa->getManyToManyRelatedEntities());
        $this->assertCount(2, $entity_b->getManyToManyRelatedEntities());
        $this->assertCount(2, $entity_bb->getManyToManyRelatedEntities());

        $entity_b->removeManyToManyRelatedEntity($entity_a);
        $entity_b->removeManyToManyRelatedEntity($entity_aa);
        $entity_bb->removeManyToManyRelatedEntity($entity_a);
        $entity_bb->removeManyToManyRelatedEntity($entity_aa);

        $this->assertCount(0, $entity_a->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_aa->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_b->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_bb->getManyToManyRelatedEntities());
    }

    public function testDummyEntityC()
    {
        $entity_c = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityCRepository::class)
            ->createEntity();

        $this->assertInstanceOf(DummyEntityC::class, $entity_c);

        return $entity_c;
    }

    /**
     *
     * @depends testDummyEntityA
     * @depends testDummyEntityB
     * @depends testDummyEntityC
     */
    public function testAutoGeneratedPropertiesNames(DummyEntityA $entity_a, DummyEntityB $entity_b, DummyEntityC $entity_c)
    {
        $this->assertObjectHasAttribute('one_to_one_dummy_entity_a', $entity_b);
        $this->assertObjectHasAttribute('one_to_many_dummy_entities_a', $entity_b);
        $this->assertObjectHasAttribute('many_to_one_dummy_entity_a', $entity_b);
        $this->assertObjectHasAttribute('many_to_many_dummy_entities_a', $entity_b);

        $this->assertObjectHasAttribute('mid_many_to_many_entities_ac_a', $entity_a);
        $this->assertObjectHasAttribute('mid_many_to_many_entities_ca_c', $entity_c);

        return $entity_c;
    }

    /**
     *
     * @depends testDummyEntityA
     * @depends testAutoGeneratedPropertiesNames
     */
    public function testSetterAndGetterParameters(DummyEntityA $entity_a, DummyEntityC $entity_c)
    {
        $entity_c->setOneToOneRelatedEntity($entity_a);

        $this->assertObjectHasAttribute('setter', $entity_c);
        $this->assertObjectHasAttribute('setter', $entity_a);
        $this->assertArrayHasKey('one_to_one_dummy_entity_a', $entity_c->setter);
        $this->assertArrayHasKey('one_to_one_dummy_entity_c', $entity_a->setter);

        $this->assertEquals($entity_a, $entity_c->getOneToOneRelatedEntity($entity_a));
        $this->assertObjectHasAttribute('getter', $entity_c);
        $this->assertObjectHasAttribute('getter', $entity_a);
        $this->assertArrayHasKey('one_to_one_dummy_entity_a', $entity_c->getter);
        $this->assertArrayHasKey('one_to_one_dummy_entity_c', $entity_a->getter);

        $entity_c->addManyToManyRelatedEntity($entity_a);
        $this->assertCount(1, $entity_c->getManyToManyRelatedEntities());

        $this->assertCount(1, $entity_c->getManyToManyMidRelations());

        $this->assertEquals(
            count($entity_c->mid_many_to_many_entities_ca_c),
            count($entity_a->mid_many_to_many_entities_ac_a)
        );

        $this->assertEquals(
            reset($entity_c->mid_many_to_many_entities_ca_c),
            reset($entity_a->mid_many_to_many_entities_ac_a)
        );

        $this->assertEquals(
            key($entity_c->mid_many_to_many_entities_ca_c),
            key($entity_a->mid_many_to_many_entities_ac_a)
        );

        $this->assertInstanceOf(\stdClass::class, $relation = $entity_c->getManyToManyMidRelationFrom($entity_a));
        $this->assertObjectHasAttribute('setter', $relation);
        $this->assertArrayHasKey('many_to_many_dummy_setter', $relation->setter);
        $this->assertObjectHasAttribute('getter', $relation);
        $this->assertArrayHasKey('many_to_many_dummy_getter', $relation->getter);

        return $relation;
    }

    /**
     *
     * @depends testSetterAndGetterParameters
     */
    public function testMidRelationPropertiesNames($relation)
    {
        $this->assertObjectHasAttribute('dummy_entity_a', $relation);
        $this->assertObjectHasAttribute('dummy_entity_c', $relation);
        $this->assertInstanceOf(DummyEntityA::class, $relation->dummy_entity_a);
        $this->assertInstanceOf(DummyEntityC::class, $relation->dummy_entity_c);
    }

    public function testDummyEntityD()
    {
        $entity_d = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityDRepository::class)
            ->createEntity();

        $this->assertInstanceOf(DummyEntityD::class, $entity_d);

        return $entity_d;
    }

    /**
     *
     * @depends testDummyEntityD
     */
    public function testSelfOneToOne(DummyEntityD $entity_d)
    {
        $this->assertNull($entity_d->getOneToOneRelatedEntity());

        $entity_dd = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityDRepository::class)
            ->createEntity();

        $entity_d->setOneToOneRelatedEntity($entity_dd);

        $this->assertEquals($entity_dd, $entity_d->getOneToOneRelatedEntity());
        $this->assertEquals($entity_d, $entity_dd->getOneToOneRelatedEntity());

        $entity_d->setOneToOneRelatedEntity(null);

        $this->assertNull($entity_d->getOneToOneRelatedEntity());
        $this->assertNull($entity_dd->getOneToOneRelatedEntity());

        $entity_dd->setOneToOneRelatedEntity($entity_d);

        $this->assertEquals($entity_d, $entity_dd->getOneToOneRelatedEntity());
        $this->assertEquals($entity_dd, $entity_d->getOneToOneRelatedEntity());

        $entity_dd->setOneToOneRelatedEntity(null);

        $this->assertNull($entity_dd->getOneToOneRelatedEntity());
        $this->assertNull($entity_d->getOneToOneRelatedEntity());
    }

    /**
     *
     * @depends testDummyEntityD
     */
    public function testSelfOneToMany(DummyEntityD $entity_d)
    {
        $entity_dd = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityDRepository::class)
            ->createEntity();

        $entity_ddd = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityDRepository::class)
            ->createEntity();

        $this->assertCount(0, $entity_d->getOneToManyRelatedEntities());

        $this->assertNull($entity_d->getManyToOneRelatedEntity());
        $this->assertNull($entity_dd->getManyToOneRelatedEntity());

        $entity_d->addOneToManyRelatedEntity($entity_dd);
        $entity_d->addOneToManyRelatedEntity($entity_ddd);

        $this->assertCount(2, $entity_d->getOneToManyRelatedEntities());
        $this->assertEquals($entity_d, $entity_dd->getManyToOneRelatedEntity());
        $this->assertEquals($entity_d, $entity_ddd->getManyToOneRelatedEntity());

        $entity_d->removeOneToManyRelatedEntity($entity_dd);
        $entity_d->removeOneToManyRelatedEntity($entity_ddd);

        $this->assertCount(0, $entity_d->getOneToManyRelatedEntities());
        $this->assertNull($entity_dd->getManyToOneRelatedEntity());
        $this->assertNull($entity_ddd->getManyToOneRelatedEntity());

        $entity_dd->setManyToOneRelatedEntity($entity_d);
        $entity_ddd->setManyToOneRelatedEntity($entity_d);

        $this->assertCount(2, $entity_d->getOneToManyRelatedEntities());
        $this->assertEquals($entity_d, $entity_dd->getManyToOneRelatedEntity());
        $this->assertEquals($entity_d, $entity_ddd->getManyToOneRelatedEntity());

        $entity_dd->setManyToOneRelatedEntity(null);
        $entity_ddd->setManyToOneRelatedEntity(null);

        $this->assertCount(0, $entity_d->getOneToManyRelatedEntities());
        $this->assertNull($entity_dd->getManyToOneRelatedEntity());
        $this->assertNull($entity_ddd->getManyToOneRelatedEntity());
    }

    /**
     *
     * @depends testDummyEntityD
     */
    public function testSelfManyToMany($entity_d)
    {
        $entity_dd = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityDRepository::class)
            ->createEntity();

        $this->assertCount(0, $entity_d->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_dd->getManyToManyRelatedEntities());

        $entity_d->addManyToManyRelatedEntity($entity_dd);

        $this->assertCount(1, $entity_d->getManyToManyRelatedEntities());
        $this->assertCount(1, $entity_dd->getManyToManyRelatedEntities());

        $entity_d->removeManyToManyRelatedEntity($entity_dd);

        $this->assertCount(0, $entity_d->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_dd->getManyToManyRelatedEntities());

        $entity_dd->addManyToManyRelatedEntity($entity_d);

        $this->assertCount(1, $entity_d->getManyToManyRelatedEntities());
        $this->assertCount(1, $entity_dd->getManyToManyRelatedEntities());

        $entity_dd->removeManyToManyRelatedEntity($entity_d);

        $this->assertCount(0, $entity_d->getManyToManyRelatedEntities());
        $this->assertCount(0, $entity_dd->getManyToManyRelatedEntities());
    }
}
