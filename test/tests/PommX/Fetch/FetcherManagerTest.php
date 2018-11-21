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

namespace App\Tests\Pommx\Fetch;

use App\Tests\AbstractTestCase;

use App\Tests\Pommx\Fetch\Dataset\DummyDog\DummyDog;
use App\Tests\Pommx\Fetch\Dataset\DummyDog\DummyDogRepository;

use App\Tests\Pommx\Fetch\Dataset\DummyPerson\DummyPerson;
use App\Tests\Pommx\Fetch\Dataset\DummyPerson\DummyPersonRepository;

use App\Tests\Pommx\Fetch\Dataset\DummyPersonDog\DummyPersonDog;
use App\Tests\Pommx\Fetch\Dataset\DummyPersonDog\DummyPersonDogRepository;

use App\Tests\TestTools\TestQueries;

class FetcherManagerTest extends AbstractTestCase
{
    use TestQueries;

    private $person_repo;

    protected function setUp()
    {
        parent::setUp();

        $this->person_repo = $this->pomm->getDefaultSession()->getRepository(DummyPerson::class);
    }

    public function testModeJoin()
    {
        // Expected queries
        $expected_queries = [
            [
                'sql' => 'SELECT dummy_schema_dummy_person_alias."name" as "name", array_agg(dummy_schema_dummy_dog_2_alias) FILTER (WHERE dummy_schema_dummy_dog_2_alias.name != \'\'::text) as "dogs", array_agg(dummy_schema_dummy_dog_4_alias) as "dogs_from_callback" FROM dummy_schema.dummy_person dummy_schema_dummy_person_alias LEFT OUTER JOIN dummy_schema.dummy_person_dog AS dummy_schema_dummy_person_dog_1_alias ON dummy_schema_dummy_person_alias.name = dummy_schema_dummy_person_dog_1_alias.person_name LEFT OUTER JOIN dummy_schema.dummy_dog AS dummy_schema_dummy_dog_2_alias ON dummy_schema_dummy_person_dog_1_alias.dog_name = dummy_schema_dummy_dog_2_alias.name  LEFT OUTER JOIN dummy_schema.dummy_person_dog AS dummy_schema_dummy_person_dog_3_alias ON dummy_schema_dummy_person_alias.name = dummy_schema_dummy_person_dog_3_alias.person_name RIGHT OUTER JOIN dummy_schema.dummy_dog AS dummy_schema_dummy_dog_4_alias ON dummy_schema_dummy_person_dog_3_alias.dog_name = dummy_schema_dummy_dog_4_alias.name  WHERE dummy_schema_dummy_person_alias."name" = $*::varchar AND TRUE GROUP BY dummy_schema_dummy_person_alias."name" ORDER BY dummy_schema_dummy_person_alias."name" ASC',
                'values' => ['someone']
            ]
        ];

        // Inject expected results.
        $person_repo = $this->person_repo;
        $this->person_repo->testToolInjectMethodeResults(
            'findByPkFromQb',
            function (array $primary_key) use ($person_repo) {
                $values = $primary_key + ['dummy_person_mother_name' => 'mom'];
                return $person_repo->createEntity($values);
            }
        );

        $someone = $this->person_repo->findByPkFromQb(['name' => 'someone']);

        $this->toolTestListenedQueries($this->person_repo, $expected_queries);
        $this->assertEquals('someone', $someone->name);
        $this->assertEquals('mom', $someone->dummy_person_mother_name);

        return $someone;
    }

    /**
     *
     * @depends testModeJoin
     */
    public function testModeProxy(DummyPerson $dummy_person)
    {
        // Proxy not created, father primary misssing
        $this->assertNull($dummy_person->proxy_father);

        $this->assertInstanceOf(DummyPerson::class, $dummy_person->proxy_mother);
        $this->assertTrue($dummy_person->proxy_mother->isStatus(DummyPerson::STATUS_PROXY));
        $this->assertEquals('mommy', $dummy_person->proxy_mother->nickname);
        $this->assertEquals($this->person_repo->getEntityRef(['name' => 'mom']), $dummy_person->proxy_mother);

        return $dummy_person;
    }

    /**
     *
     * @depends testModeProxy
     */
    public function testModeLazy(DummyPerson $dummy_person)
    {
        $proxy_mother = $dummy_person->proxy_mother;

        $this->assertNull($proxy_mother->lazy_dogs_relations);
        $this->toolTestListenedQueries($this->person_repo, []);

        $expected_queries = [
            [
                'sql' => 'SELECT dummy_schema_dummy_person_alias."dummy_person_father_name" as "dummy_person_father_name", dummy_schema_dummy_person_alias."dummy_person_mother_name" as "dummy_person_mother_name", dummy_schema_dummy_person_alias."name" as "name", dummy_schema_dummy_person_alias."nickname" as "nickname", array_agg(dummy_schema_dummy_person_dog_5_alias) FILTER (WHERE dummy_schema_dummy_person_dog_5_alias.name != \'\'::text) as "lazy_dogs_relations", array_agg(dummy_schema_dummy_person_dog_6_alias) as "lazy_dogs_relations_from_callback" FROM dummy_schema.dummy_person dummy_schema_dummy_person_alias LEFT OUTER JOIN dummy_schema.dummy_person_dog AS dummy_schema_dummy_person_dog_5_alias ON dummy_schema_dummy_person_alias.name = dummy_schema_dummy_person_dog_5_alias.person_name  RIGHT OUTER JOIN dummy_schema.dummy_person_dog AS dummy_schema_dummy_person_dog_6_alias ON dummy_schema_dummy_person_alias.name = dummy_schema_dummy_person_dog_6_alias.person_name  WHERE dummy_schema_dummy_person_alias."name" = $*::varchar AND TRUE GROUP BY dummy_schema_dummy_person_alias."name" ORDER BY dummy_schema_dummy_person_alias."name" ASC',
                'values' => ['mom']
            ]
        ];

        $tester = $this;
        $dogs_relations = [
            $this->pomm->getDefaultSession()->getRepository(DummyPersonDog::class)
                ->createEntity(['person_name' => $proxy_mother->name, 'dog_name' => 'snoopy'])
        ];

        $this->person_repo->testToolInjectMethodeResults(
            'findByPkFromQb',
            function (array $primary_key, array $fields = null) use ($tester, $proxy_mother, $dogs_relations) {
                $expected_lazy_fields = [
                    'nickname',
                    'dummy_person_father_name',
                    'dummy_person_mother_name',
                    'lazy_dogs_relations',
                    'lazy_dogs_relations_from_callback'
                ];

                $tester->assertEquals(['name' => 'mom'], $primary_key);
                $tester->assertInternalType('array', $fields);
                $tester->assertCount(count($expected_lazy_fields), $fields);
                foreach ($expected_lazy_fields as $field) {
                    $tester->assertContains($field, $fields);
                }

                $proxy_mother->lazy_dogs_relations = $dogs_relations;

                return $proxy_mother;
            }
        );

        //"$proxy_mother->getLazyDogsRelations()" is supposed to trigger "AbstractEntity::get()",
        // that will call the fetcher manager
        $result = $proxy_mother->getLazyDogsRelations();

        $this->toolTestListenedQueries($this->person_repo, $expected_queries);
        $this->assertInternalType('array', $result);
        $this->assertCount(count($dogs_relations), $result);
        foreach ($result as $index => $relation) {
            $this->assertContainsOnlyInstancesOf(DummyPersonDog::class, $result);
            $this->assertEquals($dogs_relations[$index], $relation);
        }

        return $dummy_person;
    }

    /**
     *
     * @depends testModeLazy
     */
    public function testModeExtraLazy(DummyPerson $dummy_person)
    {
        $this->assertNull($dummy_person->extra_lazy_mother);
        $this->toolTestListenedQueries($this->person_repo, []);

        $expected_queries = [
            [
            'sql' => 'SELECT dummy_schema_dummy_person_alias."name" as "name", dummy_schema_dummy_person_7_alias as "extra_lazy_mother" FROM dummy_schema.dummy_person dummy_schema_dummy_person_alias LEFT OUTER JOIN dummy_schema.dummy_person AS dummy_schema_dummy_person_7_alias ON dummy_schema_dummy_person_alias.dummy_person_mother_name = dummy_schema_dummy_person_7_alias.name  WHERE dummy_schema_dummy_person_alias."name" = $*::varchar AND TRUE GROUP BY dummy_schema_dummy_person_alias."name", extra_lazy_mother ORDER BY dummy_schema_dummy_person_alias."name" ASC',
                'values' => ['someone']
            ]
        ];

        $tester = $this;
        $repo   = $this->person_repo;
        $ref_mom = $repo->getEntityRef(['name' => 'mom']);

        $this->person_repo->testToolInjectMethodeResults(
            'findByPkFromQb',
            function (array $primary_key, array $fields = null) use ($tester, $dummy_person, $ref_mom) {
                $expected_lazy_fields = ['extra_lazy_mother'];

                $tester->assertEquals(['name' => 'someone'], $primary_key);
                $tester->assertInternalType('array', $fields);
                $tester->assertCount(count($expected_lazy_fields), $fields);
                $tester->assertContains('extra_lazy_mother', $fields);

                $dummy_person->extra_lazy_mother = $ref_mom;

                return $dummy_person;
            }
        );

        //"$dummy_person->getExtraLazyMother()" is supposed to trigger "AbstractEntity::get()",
        // that will call the fetcher manager
        $result = $dummy_person->getExtraLazyMother();
        $this->toolTestListenedQueries($this->person_repo, $expected_queries);
        $this->assertInstanceOf(DummyPerson::class, $result);
        $this->assertEquals($ref_mom, $result);
    }
}
