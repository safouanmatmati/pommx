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

namespace PommX\EntityManager\Annotation;

/**
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class CascadeDeletes
{
    /**
     * Cascade deletes definition.
     * Expects {"name", "class", "map"} attributes.
     *
     * @var      array
     * @Required
     */
    public $list;
}
