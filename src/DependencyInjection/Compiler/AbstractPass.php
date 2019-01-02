<?php

/*
 * This file is part of the Pommx package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pommx\DependencyInjection\Compiler;

use Pommx\DependencyInjection\Compiler\PassInterface;
use Pommx\DependencyInjection\PommxExtension;

abstract class AbstractPass implements PassInterface
{
    /**
     * Pommx extension.
     *
     * @var PommxExtension
     */
    private $extension;

    /**
     * [__construct description]
     * @param PommxExtension $extension [description]
     */
    public function __construct(PommxExtension $extension = null)
    {
        $this->extension = $extension;
    }

    public function setPommxExtension(PommxExtension $extension)
    {
        $this->extension = $extension;
    }

    public function getPommxExtension(): PommxExtension
    {
        return $this->extension;
    }
}
