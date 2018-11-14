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

namespace PommX\Entity\Traits;

use PommX\Relation\RelationTrait as BaseRelationTrait;

trait RelationTrait
{
    use BaseRelationTrait;

    /**
     * {@inheritdoc}
     */
    public function &relationGetSandbox(): array
    {
        return $this->getSandbox(BaseRelationTrait::class);
    }
}
