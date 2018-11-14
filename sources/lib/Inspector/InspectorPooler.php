<?php

/*
 * This file is part of the PommX package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PommX\Inspector;

use PommProject\Foundation\Inspector\InspectorPooler as PoomInspectorPooler;

use PommX\Inspector\Inspector;

class InspectorPooler extends PoomInspectorPooler
{
    /**
     * Returns PommX inspector instead of Pomm default one.
     * 
     * @param  null|string $identifier
     * @return Inspector
     */
    public function getClient($identifier = null)
    {
        if ($identifier === null) {
            $identifier = Inspector::class;
        }

        return parent::getClient($identifier);
    }
}
