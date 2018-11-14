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

namespace App\Tests\PommX\Fetch\Dataset\DummyPerson;

use PommX\Repository\AbstractRepository;
use PommX\Repository\Join;

use App\Tests\PommX\Fetch\Dataset\DummyPerson\DummyPerson;
use App\Tests\PommX\Fetch\Dataset\DummyPerson\DummyPersonStructure;

use App\Tests\PommX\Fetch\Dataset\DummyPersonDog\DummyPersonDog;

use App\Tests\PommX\Fetch\Dataset\RepositoryTestTools;

class DummyPersonRepository extends AbstractRepository
{
    use RepositoryTestTools;

    /**
     * Repository constructor.
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct(
            new DummyPersonStructure(),
            DummyPerson::class
        );
    }

    public static function fetchDogsRelations(array $arguments): Join
    {
        $pomm = $arguments['pomm'];

        $person_repo         = $pomm->getDefaultSession()->getRepository($arguments['class']);
        $person_relation     = $person_repo->getStructure()->getRelation();
        $person_dog_relation = $pomm->getDefaultSession()
            ->getRepository(DummyPersonDog::class)->getStructure()->getRelation();

        $join = (new Join($person_repo->getExceptionManager()))
            ->setType(Join::TYPE_RIGHT)
            ->setSource($person_relation)
            ->setRelated($person_dog_relation)
            ->setMappedCondition(['name' => 'person_name']);

        $join->setField(
            $arguments['property'],
            sprintf(
                "array_agg(%s)",
                $join->getRelatedAlias(),
                $join->getRelatedAlias(),
                'person_name'
            ),
            sprintf('%s[]', DummyPersonDog::class)
        );

        return $join;
    }

    public static function fetchDogsJoin(array $arguments): Join
    {
        $pomm = $arguments['pomm'];

        $person_repo = $pomm->getDefaultSession()->getRepository($arguments['class']);
        $person_relation     = $person_repo->getStructure()->getRelation();
        $person_dog_relation = $pomm->getDefaultSession()->getRepository(DummyPersonDog::class)->getStructure()->getRelation();
        $dog_relation        = $pomm->getDefaultSession()->getRepository($arguments['custom_args'])->getStructure()->getRelation();

        $join = (new Join($person_repo->getExceptionManager()))
            ->setType(Join::TYPE_LEFT)
            ->setSource($person_relation)
            ->setRelated($person_dog_relation)
            ->setMappedCondition(['name' => 'person_name']);

        $related_join = (new Join($person_repo->getExceptionManager()))
            ->setType(Join::TYPE_RIGHT)
            ->setRelated($dog_relation)
            ->setMappedCondition(['dog_name' => 'name']);

        $join->addJoin($related_join);

        $join->setField(
            $arguments['property'],
            sprintf(
                "array_agg(%s)",
                $related_join->getRelatedAlias(),
                $related_join->getRelatedAlias(),
                'name'
            ),
            sprintf('%s[]', $arguments['custom_args'])
        );

        return $join;
    }
}
