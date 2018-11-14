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

use PommX\Repository\AbstractRowStructure;

class DummyPersonStructure extends AbstractRowStructure
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
        $relation = 'dummy_schema.dummy_person';
        $this->setRelation($relation)
            ->setPrimaryKey(['name'])
            ->setForeignKey(
                [
                    'dummy_person_father_name' => $relation.'.name',
                    'dummy_person_mother_name' => $relation.'.name'
                ]
            )
            ->addField('dummy_person_father_name', 'varchar')
            ->addField('dummy_person_mother_name', 'varchar')
            ->addField('name', 'varchar')
            ->addField('nickname', 'varchar');
    }
}
