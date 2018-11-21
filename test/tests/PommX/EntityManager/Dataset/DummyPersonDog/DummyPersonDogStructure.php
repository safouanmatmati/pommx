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

namespace App\Tests\Pommx\EntityManager\Dataset\DummyPersonDog;

use Pommx\Repository\AbstractRowStructure;

class DummyPersonDogStructure extends AbstractRowStructure
{
    /**
     * __construct
     *
     * Structure definition.
     *
     * @access public
     */
    public function __construct()
    {
        $relation = 'dummy_schema.dummy_person_dog';
        $this->setRelation($relation)
            ->setPrimaryKey(['person_name', 'dog_name'])
            ->setForeignKey(
                [
                    'person_name' => 'dummy_schema.dummy_person.name',
                    'dog_name' => 'dummy_schema.dummy_dog.name'
                ]
            )
            ->addField('person_name', 'varchar')
            ->addField('dog_name', 'varchar');
    }
}
