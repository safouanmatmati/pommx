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

namespace App\Tests\Pommx\Fetch\Dataset;

use App\Tests\TestTools\RepositoryTestTools as BaseRepositoryTestTools;

use Pommx\Entity\AbstractEntity;

trait RepositoryTestTools
{
    use BaseRepositoryTestTools;

    /**
     * {@inheritdoc}
     */
    public function findByPkFromQb(array $primary_key, array $fields = null, array $context = null): ?AbstractEntity
    {
        parent::findByPkFromQb($primary_key, $fields, $context);

        return $this->testToolGetInjectedMethodeResults('findByPkFromQb', $primary_key, $fields, $context);
    }
}
