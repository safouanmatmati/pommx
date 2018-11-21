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
 * @Target({"PROPERTY"})
 */
class CascadeDelete
{
    /**
     * Class do cascade delete.
     *
     * @var string
     */
    public $class;
}
