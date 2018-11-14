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

namespace App\Tests\PommX\Repository;

use PommX\Repository\AbstractRepository;

use App\Tests\AbstractTestCase;
use App\Tests\PommX\Repository\Dataset\DummyEntity\DummyEntity;
use App\Tests\PommX\Repository\Dataset\DummyEntity\DummyEntityRepository;

class RepositoryTest extends AbstractTestCase
{
    public function testGetRepository()
    {
        $repository = $this->pomm->getDefaultSession()->getRepository(DummyEntityRepository::class);
        $this->assertInstanceOf(DummyEntityRepository::class, $repository);
        $this->assertInstanceOf(AbstractRepository::class, $repository);

        return $repository;
    }

    //
    //  TODO use prophecy to check that thoses functions has been called
    //
    //  setExceptionManager;
    //  initializeQueryBuilderTrait;
    //  initializeEntityTrait;

    /**
     *
     * @depends testGetRepository
     */
    public function testInitialize(DummyEntityRepository $repository)
    {
        $session = $repository->getSession();

        $this->assertTrue(
            $session->hasClient(
                $repository->getClientType(),
                $repository->getClientIdentifier()
            )
        );

        //TODO use prophecy to check that "initializePgEntityConverter" has been called

        $this->assertTrue($repository->isInitialized());

        return $repository;
    }
}
