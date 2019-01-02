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

use Pommx\DependencyInjection\PommxExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

interface PassInterface extends CompilerPassInterface
{
    public function __construct(PommxExtension $extension);

    public function setPommxExtension(PommxExtension $extension);

    public function getPommxExtension(): PommxExtension;
}
