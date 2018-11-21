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

namespace Pommx\Entity\Traits;

use Pommx\MapProperties\MapPropertiesTrait as BaseMapPropertiesTrait;

trait MapPropertiesTrait
{
    use BaseMapPropertiesTrait;

    /**
     * {@inheritdoc}
     */
    public function &mapPropGetSandbox(): array
    {
        return $this->getSandbox(BaseMapPropertiesTrait::class);
    }
}
