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

namespace App\Tests\PommX\Repository\Dataset\DummyEntity;

use PommX\Repository\AbstractRowStructure;

class DummyEntityStructure extends AbstractRowStructure
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
        $this->setRelation('dummy_schema.dummy_entity')
        ->setPrimaryKey(['primary_key'])
        ->addField('primary_key', 'varchar')
        ->addField('field_2', 'text')
        ->addField('field_3', 'int4');
    }
}
