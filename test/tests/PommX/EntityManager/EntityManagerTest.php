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

namespace App\Tests\Pommx\EntityManager;

use Pommx\EntityManager\EntityManager;

use App\Tests\AbstractTestCase;

use App\Tests\Pommx\EntityManager\Dataset\DummyDog\DummyDog;
use App\Tests\Pommx\EntityManager\Dataset\DummyDog\DummyDogRepository;

use App\Tests\Pommx\EntityManager\Dataset\DummyPerson\DummyPerson;
use App\Tests\Pommx\EntityManager\Dataset\DummyPerson\DummyPersonRepository;

use App\Tests\Pommx\EntityManager\Dataset\DummyPersonDog\DummyPersonDog;
use App\Tests\Pommx\EntityManager\Dataset\DummyPersonDog\DummyPersonDogRepository;

use App\Tests\TestTools\TestQueries;

class EntityManagerTest extends AbstractTestCase
{
    use TestQueries;

    private $repositories;

    protected function setUp()
    {
        parent::setUp();

        $this->repositories = [
            DummyPersonRepository::class =>
                $this->pomm->getDefaultSession()->getRepository(DummyPerson::class),
            DummyDogRepository::class =>
                $this->pomm->getDefaultSession()->getRepository(DummyDog::class),
            DummyPersonDogRepository::class =>
                $this->pomm->getDefaultSession()->getRepository(DummyPersonDog::class)
        ];

        foreach ($this->repositories as $repo) {
            $repo->testToolClearListenedQueries();
        }
    }

    public function testGetEntityManager()
    {
        $entity_manager = $this->pomm->getDefaultSession()->getEntityManager();
        $this->assertInstanceOf(EntityManager::class, $entity_manager);

        return $entity_manager;
    }

    /**
     *
     * @depends testGetEntityManager
     */
    public function testPersist(EntityManager $entity_manager)
    {
        $repositories = $this->getRepositories();

        $person_repo  = $repositories[DummyPersonRepository::class];
        $dog_repo     = $repositories[DummyDogRepository::class];

        $father       = $person_repo->createEntity(['name' => 'father']);
        $mother       = $person_repo->createEntity(['name' => 'mother']);
        $child        = $person_repo->createEntity(['name' => 'child']);
        $mother_child = $person_repo->createEntity(['name' => 'mother_child']);
        $dog          = $dog_repo->createEntity(['name' => 'dog']);

        $child->setFather($father);
        $child->setMother($mother);
        $mother_child->setMother($mother);

        $father->addDog($dog);
        $child->addDog($dog);
        $mother_child->addDog($dog);

        // Disables cascade persist
        $entity_manager->persist($mother, false);

        $this->assertTrue($entity_manager->contains($mother));
        $this->assertFalse($entity_manager->contains($father));
        $this->assertFalse($entity_manager->contains($dog));
        $this->assertFalse($entity_manager->contains($child));
        $this->assertFalse($entity_manager->contains($mother_child));

        // Allows cascade persist
        $entity_manager->persist($father);

        $this->assertTrue($entity_manager->contains($mother));
        $this->assertTrue($entity_manager->contains($father));
        $this->assertTrue($entity_manager->contains($dog));

        // DummyPerson has cascade persist disabled
        $this->assertFalse($entity_manager->contains($child));
        $this->assertFalse($entity_manager->contains($mother_child));

        $entity_manager->persist($child);

        $this->assertTrue($entity_manager->contains($mother));
        $this->assertTrue($entity_manager->contains($father));
        $this->assertTrue($entity_manager->contains($dog));
        $this->assertTrue($entity_manager->contains($child));
        $this->assertFalse($entity_manager->contains($mother_child));

        $entity_manager->persist($mother_child);

        $this->assertTrue($entity_manager->contains($mother));
        $this->assertTrue($entity_manager->contains($father));
        $this->assertTrue($entity_manager->contains($dog));
        $this->assertTrue($entity_manager->contains($child));
        $this->assertTrue($entity_manager->contains($mother_child));

        $persisted = $entity_manager->getPersisted();
        // DummyPersonDog are persisted
        $this->assertCount(8, $persisted);

        return [
            'father'       => $father,
            'mother'       => $mother,
            'child'        => $child,
            'mother_child' => $mother_child,
            'dog'          => $dog
        ];
    }

    /**
     *
     * @depends testGetEntityManager
     * @depends testPersist
     */
    public function testInsert(EntityManager $entity_manager)
    {
        $expected_queries = [
            DummyPersonRepository::class => [
                [
                    'sql' => 'INSERT INTO dummy_schema.dummy_person ("name") VALUES ($*::varchar),($*::varchar) RETURNING "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' => ['mother', 'father']
                ],
                [
                    'sql' => 'INSERT INTO dummy_schema.dummy_person ("dummy_person_father_name", "dummy_person_mother_name", "name") VALUES ($*::varchar,$*::varchar,$*::varchar) RETURNING "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' => ['father', 'mother', 'child']
                ],
                [
                    'sql' => 'INSERT INTO dummy_schema.dummy_person ("dummy_person_mother_name", "name") VALUES ($*::varchar,$*::varchar) RETURNING "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' => ['mother', 'mother_child']
                ]
            ],
            DummyDogRepository::class => [
                [
                    'sql' => 'INSERT INTO dummy_schema.dummy_dog ("name") VALUES ($*::varchar) RETURNING "name" as "name", "nickname" as "nickname"',
                    'values' => ['dog']
                ]
            ],
            DummyPersonDogRepository::class => [
                [
                    'sql' => 'INSERT INTO dummy_schema.dummy_person_dog ("dog_name", "person_name") VALUES ($*::varchar,$*::varchar),($*::varchar,$*::varchar),($*::varchar,$*::varchar) RETURNING "person_name" as "person_name", "dog_name" as "dog_name"',
                    'values' => ['dog', 'father', 'dog', 'child', 'dog', 'mother_child']
                ]
            ]
        ];

        // Hack to simulate result from an insert on database
        foreach ($this->getRepositories() as $repo) {
            $repo->testToolInjectMethodeResults(
                'insert',
                function (array $entities) use ($repo) {
                    foreach ($entities as $entity) {
                        // Status defined as "exists"
                        $entity->setStatus(
                            [$entity::STATUS_NONE => true, $entity::STATUS_EXIST => true]
                        );
                    }

                    return $repo;
                }
            );
        }

        $this->flushAndTestQueries($entity_manager, $expected_queries);
    }

    /**
     *
     * @depends testGetEntityManager
     * @depends testPersist
     * @depends testInsert
     */
    public function testUpdate(EntityManager $entity_manager, $entities)
    {
        $expected_queries = [
            DummyPersonRepository::class => [
                [
                    'sql' => 'UPDATE dummy_schema.dummy_person AS relation_alias SET "dummy_person_father_name" = values_alias."dummy_person_father_name", "dummy_person_mother_name" = values_alias."dummy_person_mother_name", "name" = values_alias."name", "nickname" = values_alias."nickname" FROM (VALUES ($*::varchar,$*::varchar,$*::varchar,$*::varchar,$*::varchar),($*::varchar,$*::varchar,$*::varchar,$*::varchar,$*::varchar),($*::varchar,$*::varchar,$*::varchar,$*::varchar,$*::varchar),($*::varchar,$*::varchar,$*::varchar,$*::varchar,$*::varchar)) AS values_alias (dummy_person_father_name, dummy_person_mother_name, name, nickname, "current_name") WHERE relation_alias."name" = values_alias."current_name" RETURNING relation_alias."dummy_person_father_name" as "dummy_person_father_name", relation_alias."dummy_person_mother_name" as "dummy_person_mother_name", relation_alias."name" as "name", relation_alias."nickname" as "nickname"',
                    'values' => ['', '', 'mother', 'M', 'mother', '', '', 'father', 'F', 'father', 'father', 'mother', 'child', 'C', 'child', '', 'mother', 'mother_child', 'MC', 'mother_child']
                ]
            ],
            DummyDogRepository::class => [
                [
                    'sql' => 'UPDATE dummy_schema.dummy_dog AS relation_alias SET "name" = values_alias."name", "nickname" = values_alias."nickname" FROM (VALUES ($*::varchar,$*::varchar,$*::varchar)) AS values_alias (name, nickname, "current_name") WHERE relation_alias."name" = values_alias."current_name" RETURNING relation_alias."name" as "name", relation_alias."nickname" as "nickname"',
                    'values' => ['dog', 'D', 'dog']
                ]
            ],
            DummyPersonDogRepository::class => []
        ];

        $entities['father']->nickname = 'F';
        $entities['mother']->nickname = 'M';
        $entities['child']->nickname = 'C';
        $entities['mother_child']->nickname = 'MC';
        $entities['dog']->nickname = 'D';

        // Hack to simulate result from an update on database
        foreach ($this->getRepositories() as $repo) {
            $repo->testToolInjectMethodeResults(
                'update',
                function (array $entities) use ($repo) {
                    foreach ($entities as $entity) {
                        // orignal properties are update with new values
                        $entity->hydrate(
                            array_intersect_key(
                                $entity->extract(),
                                array_flip($repo->getStructure()->getFieldNames())
                            )
                        );

                        // Status defined as "exists"
                        $entity->setStatus(
                            [$entity::STATUS_NONE => true, $entity::STATUS_EXIST => true]
                        );
                    }

                    return $repo;
                }
            );
        }

        $this->flushAndTestQueries($entity_manager, $expected_queries);
    }

    /**
     *
     * @depends testGetEntityManager
     * @depends testPersist
     * @depends testUpdate
     */
    public function testDelete(EntityManager $entity_manager, $entities)
    {
        $expected_queries = [
            DummyPersonRepository::class => [
                [
                    'sql' => 'delete from dummy_schema.dummy_person where name IN ($*) returning "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' => ['father']
                ]
            ],
            DummyDogRepository::class => [],
            DummyPersonDogRepository::class => []
        ];

        $entities['father']->setStatus([$entities['father']::STATUS_TO_DELETE => true]);

        $this->flushAndTestQueries($entity_manager, $expected_queries, false);
    }

    /**
     *
     * @depends testGetEntityManager
     * @depends testPersist
     * @depends testDelete
     */
    public function testDeleteCascade(EntityManager $entity_manager, $entities)
    {
        $expected_queries = [
            DummyPersonRepository::class => [
                [
                    'comment' => 'orignal delete',
                    'sql' => 'delete from dummy_schema.dummy_person where name IN ($*) returning "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' => ['father']
                ],
                [
                    'comment' => '1 - from DummyPersonDog, already deleted',
                    'sql' => 'delete from dummy_schema.dummy_person where name IN ($*) returning "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' => ['father']
                ],
                [
                    'comment' => '5 - from DummyPersonDog',
                    'sql' => 'delete from dummy_schema.dummy_person where name IN ($*, $*) returning "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' =>  [
                        'child',
                        'mother_child'
                    ]
                ],
                [
                    'comment' => '7 - from DummyPerson',
                    'sql' => 'delete from dummy_schema.dummy_person where name IN ($*, $*) returning "dummy_person_father_name" as "dummy_person_father_name", "dummy_person_mother_name" as "dummy_person_mother_name", "name" as "name", "nickname" as "nickname"',
                    'values' =>  [
                        'father', 'mother'
                    ]
                ]
            ],
            DummyDogRepository::class => [
                [
                    'comment' => '3 - from DummyPersonDog',
                    'sql' => 'delete from dummy_schema.dummy_dog where name IN ($*) returning "name" as "name", "nickname" as "nickname"',
                    'values' => ['dog']
                ],
                [
                    'comment' => '6 - from DummyPerson, already deleted',
                    'sql' => 'delete from dummy_schema.dummy_dog where name IN ($*) returning "name" as "name", "nickname" as "nickname"',
                    'values' => ['dog']
                ]
            ],
            DummyPersonDogRepository::class => [
                [
                    'comment' => '2 - from DummyPerson',
                    'sql' => 'delete from dummy_schema.dummy_person_dog where person_name IN ($*) returning "person_name" as "person_name", "dog_name" as "dog_name"',
                    'values' => ['father']
                ],
                [
                    'comment' => '4 - from DummyDog',
                    'sql' => 'delete from dummy_schema.dummy_person_dog where dog_name IN ($*) returning "person_name" as "person_name", "dog_name" as "dog_name"',
                    'values' => ['dog']
                ],
                [
                    'comment' => '8 - from DummyPerson, already deleted',
                    'sql' => 'delete from dummy_schema.dummy_person_dog where person_name IN ($*, $*) returning "person_name" as "person_name", "dog_name" as "dog_name"',
                    'values' => ['child', 'mother_child']
                ]
            ]
        ];

        $expected_cascade_delete_results = [
            DummyPersonDogRepository::class => [
                '2' => $entities['father']->getDogsRelations(),
                '4' => (function () use ($entities) {
                    $entities['father']->removeDog($entities['dog']);

                    return $entities['dog']->getRelations();
                }),
                '8' => [],
            ],
            DummyPersonRepository::class => [
                '1' => [],
                '5' => [$entities['child'], $entities['mother_child']],
                '7' => [$entities['mother']],
            ],
            DummyDogRepository::class => [
                '3' => [$entities['dog']],
                '6' => []
            ]
        ];

        // Hack to simulate result from an delete on database
        foreach ($this->getRepositories() as $repo) {
            foreach ($expected_cascade_delete_results[get_class($repo)] as $result) {
                $repo->testToolInjectMethodeResults('deleteGrouped', $result);
            }
        }

        $entities['father']->setStatus([$entities['father']::STATUS_TO_DELETE => true]);

        $this->flushAndTestQueries($entity_manager, $expected_queries);
    }

    private function getRepositories(string $entity_class = null)
    {
        if (false == is_null($entity_class)) {
            $repo_class_finder = static::$container->get('Pommx\Tools\RepositoryClassFinder');

            return $this->repositories[$repo_class_finder->get($entity_class)];
        }

        return $this->repositories;
    }

    private function flushAndTestQueries(EntityManager $entity_manager, array $expected_queries, bool $cascade_delete = null)
    {
        $entity_manager->flush(null, null, $cascade_delete);

        foreach ($this->getRepositories() as $repo_class => $repo) {
            $this->toolTestListenedQueries($repo, $expected_queries[$repo_class]);
        }
    }
}
