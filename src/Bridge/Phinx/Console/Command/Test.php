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

namespace Pommx\Bridge\Phinx\Console\Command;

use Phinx\Console\Command\Test as PhinxTest;

use Pommx\Bridge\Phinx\Console\Command\Adapter;

class Test extends PhinxTest
{
    use Adapter;
}
