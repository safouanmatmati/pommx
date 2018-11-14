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

use PommX\Repository\AbstractRowStructure;

class DummyEntityAStructure extends AbstractRowStructure
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
        $relation = 'dummy_schema.dummy_entity_a';
        $this->setRelation($relation)
            ->setPrimaryKey(['name'])
            ->addField('name', 'varchar');
    }
}
