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

namespace Pommx\PropertyInfo\Extractor;

use Pommx\Entity\AbstractEntity;

abstract class AbstractExtractor
{
    /**
     * [supports description]
     *
     * @param  string $class
     * @return bool
     */
    final protected function supports(string $class): bool
    {
        return is_subclass_of($class, AbstractEntity::class);
    }
}
