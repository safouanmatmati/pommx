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

namespace App\Tests\Pommx\Repository\Traits;

use Pommx\Repository\QueryBuilder\QueryBuilder;

use App\Tests\AbstractTestCase;
use App\Tests\Pommx\Repository\Dataset\DummyEntity\DummyEntity;
use App\Tests\Pommx\Repository\Dataset\DummyEntity\DummyEntityRepository;

class QueryBuilderTest extends AbstractTestCase
{
    public function testGetRepository()
    {
        $repository = $this->pomm->getDefaultSession()->getRepository(DummyEntityRepository::class);
        $this->assertInstanceOf(DummyEntityRepository::class, $repository);

        return $repository;
    }

    /**
     *
     * @depends testGetRepository
     */
    public function testCreateBuilder(DummyEntityRepository $repository)
    {
        $query_builder = $repository->createBuilder("SELECT 'my test'::text WHERE 1=1;");
        
        $this->assertInstanceOf(QueryBuilder::class, $query_builder);

        return $repository;
    }
}
