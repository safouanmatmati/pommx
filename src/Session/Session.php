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

use PommProject\Foundation\Session as PommSession;

use Pommx\Repository\AbstractRepository;

class Session extends PommSession
{
    /**
     * Replaces default model pooler call by repository pooler call.
     * Return the repository client.
     *
     * @param   string $identifier
     * @return  AbstractRepository
     */
    public function getModel($identifier): AbstractRepository
    {
        return $this->getClientUsingPooler('repository', $identifier);
    }

    /**
     * Replaces default model layer pooler call by repository layer pooler call.
     * Return the repository layer client.
     * 
     * @param   string $identifier
     * @return  AbstractRepository
     */
    public function getModelLayer($identifier): AbstractRepository
    {
        return $this->getClientUsingPooler('repository_layer', $identifier);
    }
}
