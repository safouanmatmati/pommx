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

namespace App\Tests\PommX\MapProperties\Dataset;

use PommX\MapProperties\Annotation\MapValue;

use PommX\Entity\Traits\MapPropertiesTrait;

use PommX\Entity\AbstractEntity;

class DummyEntity extends AbstractEntity
{
    /**
     *
     * @MapValue(source="$related_1", property="id")
     * @var                           [type]
     */
    public $related_1_id;

    public $related_1;

    /**
     *
     * @MapValue(source="$related_2", method="getValue")
     * @var                           [type]
     */
    public $related_2_id;

    public $related_2;

    /**
     *
     * @MapValue(method="App\Tests\PommX\MapProperties\Dataset\Dummy::getStaticValue")
     * @var                                                                                           [type]
     */
    public $another_field;

    /**
     *
     * @MapValue(source="$related_4")
     * @var                           [type]
     */
    public $related_3;

    public $related_4;
}
