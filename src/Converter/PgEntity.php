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

namespace Pommx\Converter;

use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Converter\PgEntity as PommPgEntity;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;

use Pommx\Entity\AbstractEntity;
use Pommx\Repository\IdentityMapper;

use Pommx\Tools\Exception\ExceptionManager;
use Pommx\Tools\Exception\ExceptionManagerAwareInterface;
use Pommx\Tools\Exception\ExceptionManagerAwareTrait;

class PgEntity extends PommPgEntity implements ExceptionManagerAwareInterface
{
    use ExceptionManagerAwareTrait;

    /**
     *
     * @var callable[]
     */
    private $callbacks = [];

    /**
     * [cacheEntity description]
     *
     * @param  FlexibleEntityInterface $entity [description]
     * @return [type]                          [description]
     */
    public function cacheEntity(FlexibleEntityInterface $entity)
    {
        $callbacks = [
            'preCacheEntityCallback'
        ];

        $this->callCallbacks($entity, $callbacks);

        $entity = parent::cacheEntity($entity);

        $callbacks = [
            'postCacheEntityCallback'
        ];

        $entity = $this->callCallbacks($entity, $callbacks);

        return $entity;
    }

    public function initCallbacks(array $callbacks)
    {
        $this->callbacks = $callbacks;
    }

    private function getCallbacks(): array
    {
        return $this->callbacks;
    }

    private function callCallbacks(AbstractEntity $entity, array $names): AbstractEntity
    {
        $callbacks = $this->getCallbacks();

        foreach ($names as $name) {
            if (false == is_null($callback = $callbacks[$name] ?? null)) {
                try {
                    $entity = call_user_func($callback, $entity);
                } catch (\Exception $e) {
                    $this->getExceptionManager()
                        ->throw(
                            self::class,
                            __LINE__,
                            sprintf('"%s" callback call failed.', $name),
                            null,
                            $e
                        );
                }
            }
        }

        return $entity;
    }

    public function getCacheManager():IdentityMapper
    {
        return $this->identity_mapper;
    }
}
