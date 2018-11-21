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

namespace Pommx\Repository;

use PommProject\ModelManager\Model\Model;
use PommProject\Foundation\Session\Session;

use Pommx\Repository\AbstractRowStructure;
use Pommx\Repository\Layer\AbstractLayer;
use Pommx\Repository\Traits\Entity;
use Pommx\Repository\Traits\Queries;
use Pommx\Repository\QueryBuilder\Traits\QueryBuilder;
use Pommx\Repository\QueryBuilder\Extension\ExtensionsManager;

use Pommx\Entity\AbstractEntity;

use Pommx\MapProperties\MapPropertiesManager;
use Pommx\Relation\RelationsManager;
use Pommx\Fetch\FetcherManager;

use Pommx\Tools\Exception\ExceptionManagerAwareTrait;
use Pommx\Tools\Exception\ExceptionManagerAwareInterface;
use Pommx\Tools\Exception\ExceptionManagerInterface;
use Pommx\Tools\UnawareVisibilityTrait;

abstract class AbstractRepository extends Model implements ExceptionManagerAwareInterface
{
    use ExceptionManagerAwareTrait;
    use Entity;
    use Queries;
    use QueryBuilder;
    use UnawareVisibilityTrait;

    /**
     *
     * @var bool
     */
    private $initialized;

    /**
     *
     * @var string
     */
    private $entity_class;

    /**
     *
     * @var string
     */
    private $relation_alias;

    /**
     *
     * @var string
     */
    private $layer_class;

    /**
     * Repository constructor
     *
     * @param RowStructure $structure
     * @param string       $entity_class
     * @param string|null  $layer_class
     */
    public function __construct(AbstractRowStructure $structure, string $entity_class, string $layer_class = null)
    {

        if (false == is_subclass_of($entity_class, AbstractEntity::class)) {
            throw new \InvalidArgumentException(
                '"'.$entity_class.'" class has to extend "'.AbstractEntity::class.'"'
            );
        }

        $this->structure        = $structure;
        $this->layer_class      = $layer_class;
        $this->entity_class     = $this->flexible_entity_class =  trim($entity_class, "\\");
        $this->relation_alias   = sprintf('%s_alias', strtr($this->getStructure()->getRelation(), '.', '_'));
        $this->build_next_query = false;
        $this->last_builder     = null;
    }

    /**
     * getClientType
     *
     * @see ClientInterface
     */
    public function getClientType()
    {
        return 'repository';
    }


    /**
     * Defines services.
     *
     * @param  ExceptionManagerInterface $exception_manager
     * @param  ExtensionsManager         $extensions_manager
     * @param  MapPropertiesManager      $map_prop_manager
     * @param  RelationsManager   $rel_entities_manager
     * @return self
     */
    public function preInitialize(
        ExceptionManagerInterface $exception_manager,
        ExtensionsManager $extensions_manager,
        MapPropertiesManager $map_prop_manager,
        RelationsManager $rel_entities_manager,
        FetcherManager $fetcher_manager
    ): self {
        $this->setExceptionManager($exception_manager);

        $this->initializeQueryBuilderTrait($extensions_manager);
        $this->initializeEntityTrait($map_prop_manager, $rel_entities_manager, $fetcher_manager);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Replaces default PgEntity converter.
     * Allows use of callbacks just after entity creation from queries.
     *
     * @param Session $session
     */
    public function initialize(Session $session)
    {
        if (true === $this->initialized) {
            return ;
        }

        $this->initialized = true;

        parent::initialize($session);

        // Registers repository in session
        if (false == $session->hasClient($this->getClientType(), $this->getClientIdentifier())) {
            $session->registerClient($this);
        }

        // Register and initilaize entity converter
        $this->initializePgEntityConverter($session);
    }

    /**
     * Returns layer.
     *
     * @return AbstractLayer
     */
    public function getLayer(): AbstractLayer
    {
        return $this->getSession()
            ->getClientUsingPooler('repository_layer', $this->layer_class ?? static::class);
    }

    /**
     * Indicates if repository is initialized.
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Returns relation (table) name prefixed with schema.
     *
     * @param  string $repository
     * @return string
     */
    protected function getRelation(string $repository): string
    {
        return $this->getSession()
            ->getRepository($repository)
            ->getStructure()
            ->getRelation();
    }

    /**
     * Returns relation (table) alias name.
     *
     * @return string
     */
    public function getRelationAlias(): string
    {
        return $this->relation_alias;
    }

    /**
     * Returns escaped identifier prefixed with relation alias.
     *
     * @param  string $name
     * @return string
     */
    public function getAliasedIdentifier(string $name): string
    {
        return $this->getRelationAlias().'.'.$this->escapeIdentifier($name);
    }
}
