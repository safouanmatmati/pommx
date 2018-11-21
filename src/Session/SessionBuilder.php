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

namespace Pommx\Session;

use PommProject\Foundation\Converter\ConverterHolder;
use PommProject\Foundation\Client\ClientHolder;
use PommProject\Foundation\Session\Connection;
use PommProject\ModelManager\SessionBuilder as PommSessionBuilder;

use Pommx\Session\Session;

class SessionBuilder extends PommSessionBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function createSession(Connection $connection, ClientHolder $client_holder, $stamp)
    {
        $this->configuration->setDefaultValue('class:session', Session::class);

        return parent::createSession($connection, $client_holder, $stamp);
    }
}
