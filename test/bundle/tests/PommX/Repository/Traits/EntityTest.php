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

namespace App\Tests\PommX\Repository\Traits;

use PommX\Converter\PgEntity;
use PommX\Repository\IdentityMapper;

use App\Tests\AbstractTestCase;
use App\Tests\PommX\Repository\Dataset\DummyEntity\DummyEntity;
use App\Tests\PommX\Repository\Dataset\DummyEntity\DummyEntityRepository;

use App\Tests\TestTools\TestQueries;

class EntityTest extends AbstractTestCase
{
    use TestQueries;

    public function testGetRepository()
    {
        $repository = $this->pomm->getDefaultSession()->getRepository(DummyEntityRepository::class);
        $this->assertInstanceOf(DummyEntityRepository::class, $repository);

        return $repository;
    }

    /**
     *
     * @depends testGetRepository
     */
    public function testInitializePgEntityConverter(DummyEntityRepository $repository)
    {
        $session = $repository->getSession();

        $converter = $session->getConverter(DummyEntity::class)->getConverter();

        $this->assertInstanceOf(PgEntity::class, $converter);

        $this->assertInstanceOf(IdentityMapper::class, $repository->getCacheManager());

        return $repository;
    }

    /**
     *
     * @depends testInitializePgEntityConverter
     */
    public function testCreateEntity(DummyEntityRepository $repository)
    {
        $dummy_entity = $repository->createEntity();

        $this->assertInstanceOf(DummyEntity::class, $dummy_entity);

        $data = $dummy_entity->fields();

        $primary_keys = ['primary_key'];
        foreach ($primary_keys as $name) {
            $this->assertArrayHasKey($name, $data);
        }

        $default_values = ['primary_key' => null, 'field_2' => null, 'field_3' => null];
        foreach ($default_values as $name => $value) {
            $this->assertArrayHasKey($name, $data);
            $this->assertContains($value, $data);
        }

        $dummy_entity = $repository->createEntity(['primary_key' => 'my primary key']);
        $data = $dummy_entity->fields();

        $default_values['primary_key'] = 'my primary key';
        foreach ($default_values as $name => $value) {
            $this->assertArrayHasKey($name, $data);
            $this->assertContains($value, $data);
        }

        return $dummy_entity;
    }

    /**
     *
     * @depends testCreateEntity
     */
    public function testInitializeEntity(DummyEntity $dummy_entity)
    {
        $this->assertTrue($dummy_entity->isInitialized());

        return $dummy_entity;
    }

    /**
     *
     * @depends testInitializeEntity
     * @depends testInitializePgEntityConverter
     */
    public function testCacheEntity(DummyEntity $dummy_entity, DummyEntityRepository $repository)
    {
        // Cache it
        $repository->getCacheManager()->fetch(
            $dummy_entity,
            $primary_key = $repository->getStructure()->getPrimaryKey()
        );

        $dummy_entity_bis = $repository->getCacheManager()->get($dummy_entity, $primary_key);

        $this->assertEquals(spl_object_hash($dummy_entity), spl_object_hash($dummy_entity_bis));

        return $dummy_entity;
    }


    /**
     *
     * @depends testCacheEntity
     * @depends testInitializePgEntityConverter
     */
    public function testGetRef(DummyEntity $dummy_entity, DummyEntityRepository $repository)
    {
        $dummy_entity_bis = $repository->getEntityRef(['primary_key' => 'another primary key']);

        $this->assertNull($dummy_entity_bis);

        $dummy_entity_bis = $repository->getEntityRef(['primary_key' => 'my primary key']);

        $this->assertEquals(spl_object_hash($dummy_entity), spl_object_hash($dummy_entity_bis));

        return $dummy_entity;
    }

    /**
     *
     * @depends testCacheEntity
     * @depends testInitializePgEntityConverter
     */
    public function testGetProxy(DummyEntity $dummy_entity, DummyEntityRepository $repository)
    {
        $dummy_entity_bis = $repository->getEntityProxy(['primary_key' => 'my proxy primary key']);

        $this->assertInstanceOf(DummyEntity::class, $dummy_entity_bis);

        $this->assertTrue(
            $dummy_entity_bis->isStatus(
                $dummy_entity_bis::STATUS_EXIST,
                $dummy_entity_bis::STATUS_PROXY
            )
        );

        $dummy_entity_bis = $repository->getEntityProxy($dummy_entity);

        $this->assertEquals(spl_object_hash($dummy_entity), spl_object_hash($dummy_entity_bis));

        return $dummy_entity;
    }

    /**
     *
     * @depends testCacheEntity
     * @depends testInitializePgEntityConverter
     */
    public function testEntityFactory(DummyEntity $dummy_entity, DummyEntityRepository $repository)
    {
        // test nested "findOneFrom()"
        $dummy_entity_bis = $repository->entityFactory(['primary_key' => 'another primary key']);

        $expected_queries = [
            [
                "sql" => 'select "primary_key" as "primary_key", "field_2" as "field_2", "field_3" as "field_3" from dummy_schema.dummy_entity where "primary_key" = $*::varchar ',
                'values' => ['another primary key']
            ]
        ];

        // TODO use at least $this->person_repo->testToolInjectMethodeResults('findOneFrom', callback)

        $this->toolTestListenedQueries($repository, $expected_queries);
        $this->assertNotEquals(spl_object_hash($dummy_entity), spl_object_hash($dummy_entity_bis));

        // test nested "getEntityRef()"
        // TODO separeate it in another test
        // use at least $this->person_repo->testToolInjectMethodeResults('findOneFrom', callback)
        $repository->testToolClearListenedQueries();
        $dummy_entity_bis = $repository->entityFactory(['primary_key' => 'my primary key']);

        $this->toolTestListenedQueries($repository, []);
        $this->assertEquals(spl_object_hash($dummy_entity), spl_object_hash($dummy_entity_bis));
    }
}
