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

namespace App\Tests\Pommx\EntityManager\Dataset\DummyDog;

use Pommx\Repository\AbstractRowStructure;

class DummyDogStructure extends AbstractRowStructure
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
        $this->setRelation('dummy_schema.dummy_dog')
            ->setPrimaryKey(['name'])
            ->addField('name', 'varchar')
            ->addField('nickname', 'varchar');
    }
}
