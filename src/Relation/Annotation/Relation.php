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

namespace Pommx\Relation\Annotation;

use Pommx\Tools\CheckIntegrityTrait;
use Pommx\Tools\Exception\ExceptionManager;

/**
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Relation
{
    use CheckIntegrityTrait;

    /**
     *
     * @var string
     */
    public $type;

    /**
     *
     * @Required
     * @var      mixed
     */
    public $related;

    /**
     *
     * @var mixed
     */
    public $property;

    /**
     *
     * @var array
     */
    public $setter;

    /**
     *
     * @var array
     */
    public $getter;

    /**
     *
     * @var array
     */
    public $mid;
}
