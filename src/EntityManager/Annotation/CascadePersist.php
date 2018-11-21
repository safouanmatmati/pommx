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


namespace Pommx\EntityManager\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class CascadePersist
{
    /**
     * @var bool
     */
    public $cascade;

    public function cascade(): ?bool
    {
        return is_bool($this->cascade) ? $this->cascade : true;
    }
}
