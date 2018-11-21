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

namespace Pommx\Repository;

use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Model\IdentityMapper as PommIdentityMapper;

use Pommx\Entity\AbstractEntity;
use Pommx\Tools\Exception\ExceptionManagerInterface;

class IdentityMapper extends PommIdentityMapper
{
    /**
     * [private description]
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    public function __construct(ExceptionManagerInterface $exception_manager)
    {
        $this->exception_manager = $exception_manager;
    }

    /**
     * Indicates if entity is cached.
     * Returns signature used to store it or null.
     *
     * @param  AbstractEntity $entity
     * @param  array          $primary_key
     * @return string|null
     */
    public function has(AbstractEntity $entity, array $primary_key): ?string
    {
        $signature = self::getSignature($entity, $primary_key);

        if (true == is_null($signature)) {
            return null;
        }

        return isset($this->instances[$signature]) ? $signature : null;
    }

    /**
     * Return corresponding cached entity or null.
     *
     * @param  AbstractEntity $entity
     * @param  array          $primary_key
     * @return ?AbstractEntity
     */
    public function get(AbstractEntity $entity, $primary_key): ?AbstractEntity
    {
        if (true == is_string($signature = $this->has($entity, $primary_key))) {
            return $this->instances[$signature];
        }

        return null;
    }

    /**
     * Replaces cached entity with another one.
     *
     * @param  AbstractEntity $entity
     * @param  array          $primary_key
     * @return AbstractEntity
     */
    public function replace(AbstractEntity $new_entity, AbstractEntity $prev_entity, array $primary_key): AbstractEntity
    {
        $prev_signature = self::getSignature($prev_entity, $primary_key);

        if ($prev_signature === null || false == array_key_exists($prev_signature, $this->instances)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to replace cached entity.'.PHP_EOL
                    .'Entity you\'r trying to replace is not cached.'
                )
            );
        }

        if (($new_entity_class = get_class($new_entity)) != ($prev_entity_class = get_class($prev_entity))) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to replace cached entity.'.PHP_EOL
                    .'You can\'t replace "%s" entity by "%s" entity, class must match.',
                    $prev_entity_class,
                    $new_entity_class
                )
            );
        }

        $this->instances[$prev_signature] = $new_entity->hydrate($prev_entity->fields());

        return $this->instances[$prev_signature];
    }
}
