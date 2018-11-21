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

namespace App\Tests\Pommx\Relation\Dataset\DummyEntityD;

use Pommx\Relation\Annotation\Relation;

use Pommx\Entity\AbstractEntity;

class DummyEntityD extends AbstractEntity
{
    /**
     * [private description]
     *
     * @var        DummyEntityD
     * @Relation(
     *  type="oneToOne",
     *  related=DummyEntityD::class,
     *  property="right_d_entity_left_d_entity"
     * )
     */
    private $one_to_one_dummy_entity_d;

    /**
     * [private description]
     *
     * @var        DummyEntityD[]
     * @Relation(
     *  type="oneToMany",
     *  related={
     *      "class"=DummyEntityD::class,
     *      "property"="many_to_one_dummy_entity_d"
     *  }
     * )
     */
    private $one_to_many_dummy_entities_d;

    /**
     * [private description]
     *
     * @var        DummyEntityD[]
     * @Relation(
     *  type="manyToMany",
     *  related={
     *      "class"=DummyEntityD::class,
     *      "property"="many_to_many_dummy_entities_d"
     *  }
     * )
     */
    private $many_to_many_dummy_entities_d;

    public function getOneToOneRelatedEntity()
    {
        return $this->get('one_to_one_dummy_entity_d');
    }

    public function setOneToOneRelatedEntity($value = null)
    {
        $this->set('one_to_one_dummy_entity_d', $value);
    }

    public function getManyToOneRelatedEntity()
    {
        return $this->get('many_to_one_dummy_entity_d');
    }

    public function setManyToOneRelatedEntity($value = null)
    {
        $this->set('many_to_one_dummy_entity_d', $value);
    }

    public function getOneToManyRelatedEntities()
    {
        return $this->get('one_to_many_dummy_entities_d');
    }

    public function addOneToManyRelatedEntity($value)
    {
        $this->addTo('one_to_many_dummy_entities_d', $value);
    }

    public function removeOneToManyRelatedEntity($value)
    {
        $this->removeFrom('one_to_many_dummy_entities_d', $value);
    }

    public function getManyToManyRelatedEntities()
    {
        return $this->get('many_to_many_dummy_entities_d');
    }

    public function addManyToManyRelatedEntity($value)
    {
        $this->addTo('many_to_many_dummy_entities_d', $value);
    }

    public function removeManyToManyRelatedEntity($value)
    {
        $this->removeFrom('many_to_many_dummy_entities_d', $value);
    }
}
