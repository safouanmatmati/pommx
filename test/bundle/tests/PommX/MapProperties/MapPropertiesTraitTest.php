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

namespace App\Tests\PommX\MapProperties;

use App\Tests\AbstractTestCase;

use App\Tests\PommX\MapProperties\Dataset\DummyEntity;
use App\Tests\PommX\MapProperties\Dataset\DummyEntityRepository;
use App\Tests\PommX\MapProperties\Dataset\Dummy;

class MapPropertiesTraitTest extends AbstractTestCase
{

    public function testTraitIsInitializedForDummyEntity()
    {
        $dummy_entity = $this->pomm->getDefaultSession()
            ->getRepository(DummyEntityRepository::class)
            ->createEntity();

        $this->assertTrue($dummy_entity->mapPropIsInitialized());

        $dummy_entity->related_1 = (object) ['id' => 'id_from_related_1'];

        $dummy_entity->related_2 = new Dummy();

        $related_4 = (object) ['custom_key' => 'id_from_related_5'];
        $dummy_entity->related_4 = $related_4;

        return $dummy_entity;
    }

    /**
     * @depends testTraitIsInitializedForDummyEntity
     */
    public function testMapping(DummyEntity $dummy_entity)
    {
        $dummy_entity->mapPropSyncAll();

        $this->assertEquals('id_from_related_1', $dummy_entity->related_1_id);

        $this->assertEquals('value', $dummy_entity->related_2_id);

        $this->assertArrayHasKey('object', $dummy_entity->another_field);
        $this->assertArrayHasKey('source', $dummy_entity->another_field);
        $this->assertArrayHasKey('destination', $dummy_entity->another_field);

        $this->assertEquals(spl_object_hash($dummy_entity->related_3), spl_object_hash($dummy_entity->related_4));
    }
}
