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

namespace Pommx\Relation;

use Pommx\Relation\RelationsManager;
use Pommx\Entity\AbstractEntity;

trait RelationTrait
{
    /**
     * Returns "sandox" to use by "MapPropertiesManager" to store its data.
     *
     * @return array
     */
    abstract public function &relationGetSandbox(): array;

    private function relationGetManager(): RelationsManager
    {
        if (true == is_null($manager = $this->relationGetSandbox()['manager'] ?? null)) {
            $this->getExceptionManager()->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Failed to retrieve relation entities manager.'.PHP_EOL
                    .'Is "%s" have beend initialized by "%s" ?',
                    static::class,
                    RelationsManager::class
                )
            );
        }

        return $manager;
    }

    protected function relationSyncAll(): AbstractEntity
    {
        $this->relationGetManager()->syncAll(static::class, $this);
        return $this;
    }

    protected function relationSync(string $property): AbstractEntity
    {
        $this->relationGetManager()
            ->sync(static::class, $property, $this);

        return $this;
    }

    protected function relationGetMidRelationsFrom(string $property)
    {
        return $this->relationGetManager()
            ->getMidRelations(static::class, $property, $this);
    }

    protected function relationGetMidRelationWith(string $property, $related_entity)
    {
        return $this->relationGetManager()
            ->getMidRelationWith(static::class, $property, $this, $related_entity);
    }

    private function relationAutoGet(string $property)
    {
        return $this->relationGetManager()->autoGet($this, $property);
    }

    private function relationAutoSet(string $property, $value): ?bool
    {
        return $this->relationGetManager()->autoSet($this, $property, $value);
    }

    private function relationAutoAdd(string $property, $value): ?bool
    {
        return $this->relationGetManager()->autoAdd($this, $property, $value);
    }

    private function relationAutoRemove(string $property, $value): ?bool
    {
        return $this->relationGetManager()->autoRemove($this, $property, $value);
    }

    private function relationAutoHas(string $property, $value): ?bool
    {
        return $this->relationGetManager()->autoHas($this, $property, $value);
    }
}
