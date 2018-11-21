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

namespace App\Tests\Pommx\MapProperties\Dataset;

class Dummy
{
    public function getValue()
    {
        return 'value';
    }

    public static function getStaticValue($parameters)
    {
        return $parameters;
    }
}
