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

namespace Pommx\MapProperties;

use Pommx\MapProperties\MapPropertiesManager;

use Pommx\MapProperties\Annotation\MapValue as MapValueAnnotation;
use Pommx\MapProperties\Annotation\MapAlias as MapAliasAnnotation;
use Pommx\MapProperties\Annotation\MapAliases as MapAliasesAnnotation;

use Pommx\Tools\Exception\ExceptionManagerInterface;

/**
 * Allows to change property values with another one value "automaticly".
 * Usefull to keep only interesting data from object. (ex: object identifier).
 * Use "mapPropGetConfig()" method, "MapAliases" or "MapAlias" annotations to defined them.
 *
 * Property have to be mapped to a "source" that returns a value.
 * "source" can be a static method or another property.
 * For that case, and if it's an object, you can specify a precise property or one of its method to call.
 * "$this" as source object is possible too.
 *
 * @var trait
 */
trait MapPropertiesTrait
{
    /**
     * Returns "sandox" to use by "MapPropertiesManager" to store its data.
     *
     * @return array
     */
    abstract public function &mapPropGetSandbox(): array;

    /**
     * Returns "MapPropertiesManager".
     *
     * @return MapPropertiesManager
     */
    final private function mapPropGetManager(): MapPropertiesManager
    {
        if (true == is_null($manager = $this->mapPropGetSandbox()['manager'] ?? null)) {
            $this->getExceptionManager()->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Failed to retrieve "map properties" manager.'.PHP_EOL
                    .'Is "%s" initialized for "%s" entity ?',
                    __TRAIT__,
                    static::class
                )
            );
        }

        return $manager;
    }

    /**
     * Returns custom configurations
     *
     * @return array
     */
    public static function mapPropGetConfig(): array
    {
        return [];
    }

    /**
     * Apply required treatements on "__construct" call.
     *
     * @return self
     */
    final protected function mapPropOnConstruct(): self
    {
        $this->mapPropGetManager()->syncAll($this);
        return $this;
    }

    /**
     * Indicates if current trait is initialized.
     *
     * @return bool
     */
    final public function mapPropIsInitialized(): bool
    {
        return $this
            ->mapPropGetManager()
            ->isInitialized($this);
    }

    /**
     * Setter.
     *
     * Set property value and synchronize properties mapped to it.
     *
     * @param  string $property
     * @param  mixed  $value
     * @return self
     */
    final public function mapPropSet(string $property, $value): self
    {
        $this
            ->mapPropGetManager()
            ->set($this, $property, $value);

        return $this;
    }

    /**
     * Synchronize all properties mapped.
     *
     * @param array|null $names
     * @return self
     */
    final public function mapPropSyncAll(array $names = null): self
    {
        $this
            ->mapPropGetManager()
            ->syncAll($this, $names);

        return $this;
    }

    // /**
    //  * Returns aliases formatted.
    //  *
    //  * @return string|null
    //  */
    // final public function mapPropGetAliasesFormatted(): ?string
    // {
    //     return $this
    //         ->mapPropGetManager()
    //         ->getAliasesFormatted($this);
    // }

    /**
     * Synchronize properties mapped to a specific source.
     *
     * @param  string $source
     * @param  [type] $strict
     * @return self
     */
    final public function mapPropSyncFrom(string $source, bool $strict = null): self
    {
        $this
            ->mapPropGetManager()
            ->syncFrom($this, $source, $strict);
        return $this;
    }

    /**
     * Synchronize property with mapped source.
     *
     * @param  string    $property
     * @param  bool|null $strict
     * @return self
     */
    final public function mapPropSync(string $property, bool $strict = null): self
    {
        $this
            ->mapPropGetManager()
            ->sync($this, $property, $strict);
        return $this;
    }

    /**
     * Indicates if mapping rules exist.
     *
     * @return bool
     */
    final public function mapPropHasMapping(): bool
    {
        return $this
            ->mapPropGetManager()
            ->hasMapping(static::class);
    }

    /**
     * Returns lists of destinations from source.
     *
     * @return array
     */
    final public function mapPropGetDestinationsFrom(string $source): array
    {
        return $this
            ->mapPropGetManager()
            ->getDestinationsFrom(static::class, $source);
    }
}
