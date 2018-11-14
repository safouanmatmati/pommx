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

namespace App\Tests\PommX\EntityManager\Dataset;

use App\Tests\TestTools\RepositoryTestTools as BaseRepositoryTestTools;
use PommX\Repository\AbstractRepository;

trait RepositoryTestTools
{
    use BaseRepositoryTestTools;

    /**
     * {@inheritdoc}
     */
    public function deleteGrouped(array $data): array
    {
        parent::deleteGrouped($data);

        return $this->testToolGetInjectedMethodeResults('deleteGrouped', $data);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(array $entities): AbstractRepository
    {
        parent::insert($entities);

        return $this->testToolGetInjectedMethodeResults('insert', $entities);
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $entities): AbstractRepository
    {
        parent::update($entities);

        return $this->testToolGetInjectedMethodeResults('update', $entities);
    }
}
