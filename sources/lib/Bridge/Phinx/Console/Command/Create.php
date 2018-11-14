<?php

/*
 * This file is part of the PommX package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PommX\Bridge\Phinx\Console\Command;

use Phinx\Console\Command\Create as PhinxCreate;

use PommX\Bridge\Phinx\Console\Command\PommAwareTrait;

class Create extends PhinxCreate
{
    use PommAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->adapte('create');
    }
}
