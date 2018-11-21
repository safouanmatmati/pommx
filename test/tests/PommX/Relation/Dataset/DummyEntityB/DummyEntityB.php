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

namespace App\Tests\Pommx\Relation\Dataset\DummyEntityB;

use Pommx\Entity\AbstractEntity;

class DummyEntityB extends AbstractEntity
{
    public function getOneToOneRelatedEntity()
    {
        return $this->get('one_to_one_dummy_entity_a');
    }

    public function setOneToOneRelatedEntity($value = null)
    {
        $this->set('one_to_one_dummy_entity_a', $value);
    }

    public function getOneToManyRelatedEntities()
    {
        return $this->get('one_to_many_dummy_entities_a');
    }

    public function addOneToManyRelatedEntity($value)
    {
        $this->addTo('one_to_many_dummy_entities_a', $value);
    }

    public function removeOneToManyRelatedEntity($value)
    {
        $this->removeFrom('one_to_many_dummy_entities_a', $value);
    }

    public function getManyToOneRelatedEntity()
    {
        return $this->get('many_to_one_dummy_entity_a');
    }

    public function setManyToOneRelatedEntity($value = null)
    {
        $this->set('many_to_one_dummy_entity_a', $value);
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

    public function getRelated2Id()
    {
        return 'related_2_id';
    }
}
