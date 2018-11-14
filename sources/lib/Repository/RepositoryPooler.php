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

namespace PommX\Repository;

use PommProject\ModelManager\Model\ModelPooler;

use PommX\Entity\AbstractEntity;

use PommX\Tools\RepositoryClassFinder;

use PommX\Tools\Exception\ExceptionManagerInterface;

use PommX\Repository\QueryBuilder\Extension\ExtensionsManager as RepositoryExtensionsManager;

use PommX\MapProperties\MapPropertiesManager;
use PommX\Relation\RelationsManager;
use PommX\Fetch\FetcherManager;

class RepositoryPooler extends ModelPooler
{
    /**
     * RepositoryClassFinder
     *
     * @var RepositoryClassFinder
     */
    private $repo_class_finder;

    /**
     * [__construct description]
     *
     * @param RepositoryClassFinder $repo_class_finder [description]
     */
    public function __construct(
        ExceptionManagerInterface $exception_manager,
        RepositoryClassFinder $repo_class_finder,
        RepositoryExtensionsManager $extensions_manager,
        MapPropertiesManager $map_prop_manager,
        RelationsManager $rel_entities_manager,
        FetcherManager $fetcher_manager
    ) {
        $this->exception_manager    = $exception_manager;
        $this->repo_class_finder    = $repo_class_finder;
        $this->extensions_manager   = $extensions_manager;
        $this->map_prop_manager     = $map_prop_manager;
        $this->rel_entities_manager = $rel_entities_manager;
        $this->fetcher_manager      = $fetcher_manager;
    }

    /**
     *
     * @see ClientPoolerInterface
     */
    public function getPoolerType()
    {
        return 'repository';
    }

    /**
     * {@inheritdoc}
     */
    protected function getClientFromPool($identifier)
    {
        if (true == is_subclass_of($identifier, AbstractEntity::class)) {
            $identifier = $this->repo_class_finder->get($identifier);
        }

        return parent::getClientFromPool($identifier);
    }


    /**
     * createModel
     *
     * @see    ClientPooler
     * @throws ModelException if incorrect
     * @return Model
     */
    protected function createClient($identifier)
    {
        if (true == is_subclass_of($identifier, AbstractEntity::class)) {
            $identifier = $this->repo_class_finder->get($identifier);
        }

        $repository = parent::createClient($identifier);

        $repository->preInitialize(
            $this->exception_manager,
            $this->extensions_manager,
            $this->map_prop_manager,
            $this->rel_entities_manager,
            $this->fetcher_manager
        );

        return $repository;
    }
}
