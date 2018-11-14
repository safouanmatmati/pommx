<?php

/*
 * This file is part of the PommX package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PommX\MapProperties;

use PommProject\Foundation\Pomm;
use Doctrine\Common\Annotations\Reader;

use PommX\MapProperties\Annotation\MapValue;
use PommX\MapProperties\Annotation\MapAlias;
use PommX\MapProperties\Annotation\MapAliases;
use PommX\MapProperties\MapPropertiesTrait;

use PommX\Entity\AbstractEntity;

use PommX\Tools\Exception\ExceptionManagerInterface;
use PommX\Tools\InheritedReflectionClass;
use PommX\Tools\CheckIntegrityTrait;
use PommX\Tools\UnawareVisibilityTrait;

// TODO remove "throwException()" function and use %s::%s$ in messages
// TODO use "checkIntegrityTrait"
//
class MapPropertiesManager
{
    use CheckIntegrityTrait;
    use UnawareVisibilityTrait;

    /**
     * [private description]
     *
     * @var Pomm
     */
    private $pomm;

    /**
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    /**
     *
     * @var Reader
     */
    private $reader;

    /**
     *
     * @var array[mixed]
     */
    private $configs;

    public function __construct(Pomm $pomm, ExceptionManagerInterface $exception_manager, Reader $reader)
    {
        $this->pomm              = $pomm;
        $this->exception_manager = $exception_manager;
        $this->reader            = $reader;
    }

    /**
     * [initialize description]
     */
    public function initialize(AbstractEntity $entity): self
    {
        if (true == $this->isInitialized($entity)) {
            return $this;
        }

        $class = get_class($entity);
        $ref   = new InheritedReflectionClass($class);

        if (false == $ref->hasTrait(MapPropertiesTrait::class)) {
            $this->exception_manager->throw(
                __LINE__,
                sprintf(
                    'Failed to initialize entity through "%s".'.PHP_EOL
                    .'"%s" class doesn\'t use "%s" trait as expected.',
                    self::class,
                    $class,
                    MapPropertiesTrait::class
                )
            );
        }

        // Inject manager to be used inside "MapPropertiesTrait" trait
        $entity->mapPropGetSandbox()['manager'] = $this;

        $this->initConfig($entity, $entity->mapPropGetConfig());

        $entity->mapPropGetSandbox()['initialized'] = true;

        $this->syncAll($entity);

        return $this;
    }

    /**
     * [isInitialized description]
     *
     * @param  AbstractEntity $entity
     * @return bool
     */
    public function isInitialized(AbstractEntity $entity): bool
    {
        return isset($entity->mapPropGetSandbox()['initialized']);
    }

    /**
     * [initConfig description]
     *
     * @param  string     $class
     * @param  array|null $parameters
     * @return self
     */
    public function initConfig(AbstractEntity $entity): self
    {
        if (true == $this->isConfigInitialized($class = get_class($entity))) {
            return $this;
        }

        $annotations    = $this->initConfigAnnotations($class);
        $properties     = $this->initConfigProperties($class, $annotations);

        $this->setConfig(
            $class,
            [
                'mapping' => $properties['mapping'],
                'sources' => $properties['sources']
            ]
        );

        return $this->setConfigInitialized($class, true);
    }

    /**
     * [set description]
     *
     * @param  AbstractEntity $entity
     * @param  string         $property
     * @param  mixed          $value
     * @return self
     */
    public function set(AbstractEntity $entity, string $property, $value): self
    {
        $this->setUnawareVisibility($entity, $property, $value);

        if (false == $this->hasMapping(get_class($entity))) {
            return $this;
        }

        return $this->syncFrom($entity, '$'.$property);
    }

    /**
     * [syncAll description]
     *
     * @param  AbstractEntity $entity
     * @param  array|null     $names
     * @return self
     */
    public function syncAll(AbstractEntity $entity, array $names = null): self
    {
        if (false == $this->hasMapping(get_class($entity))) {
            return $this;
        }

        $mapped_properties = array_keys($this->getMapping(get_class($entity)));
        $properties = false == is_null($names)
            ? array_intersect($mapped_properties, $names)
            : $mapped_properties;

        foreach ($properties as $property) {
            $this->sync($entity, $property);
        }

        return $this;
    }

    /**
     * [syncFrom description]
     *
     * @param  AbstractEntity $entity
     * @param  string         $source
     * @param  bool|null      $strict
     * @return self
     */
    public function syncFrom(AbstractEntity $entity, string $source, bool $strict = null): self
    {
        if (false == $this->hasMapping(get_class($entity))) {
            return $this;
        }

        $mapping = $this->getSources($class = get_class($entity));

        if (false == in_array($source, $mapping)) {
            if (true === $strict) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        'Failed to map properties from "%s" source'.PHP_EOL
                        .'"%s" isn\'t defined as "source" in any "%s" property.',
                        $source,
                        $source,
                        $class
                    )
                );
            }
            return $this;
        }

        foreach ($mapping as $property => $value) {
            if ($value === $source) {
                $this->setValueFrom($entity, $property, $source);
            }
        }

        return $this;
    }

    /**
     * [sync description]
     *
     * @param  AbstractEntity $entity
     * @param  string         $property
     * @param  bool|null      $strict
     * @return self
     */
    public function sync(AbstractEntity $entity, string $property, bool $strict = null): self
    {
        if (false == $this->hasMapping(get_class($entity))) {
            return $this;
        }

        if (false == array_key_exists($property, $this->getMapping($class = get_class($entity)))) {
            if (true === $strict) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        'Failed to map property "%s".'.PHP_EOL
                        .'"%s" isn\'t defined as a mapped property.',
                        $property,
                        $property
                    )
                );
            }
            return $this;
        }

        $source = $this->getSources($class)[$property] ?? null;

        $this->setValueFrom($entity, $property, $source);

        return $this;
    }

    /**
     * [hasMapping description]
     *
     * @param  string $class
     * @return bool
     */
    public function hasMapping(string $class): bool
    {
        return false == empty($this->configs[$class] ?? []);
    }

    /**
     * Returns lists of destinations from source.
     *
     * @param  string $class
     * @param  string $source
     * @return array
     */
    public function getDestinationsFrom(string $class, string $source): array
    {
        if (false == $this->hasMapping($class)) {
            return [];
        }

        $sources = [];

        foreach ($this->getSources($class) as $property => $value) {
            if ($source === $value) {
                $sources[] = $property;
            }
        }

        return $sources;
    }

    private function initConfigProperties(string $class, array $properties_annotations): array
    {
        $mapping = $sources =[];

        foreach ($properties_annotations as $name => $map_value_anno) {
            if (false == empty($map_value_anno->property)
                && false == empty($map_value_anno->method)
            ) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        '"%s" property can\'t be mapped.'.PHP_EOL
                        .'"property" and "method" parameters can\'t be defined at same time".',
                        $name,
                        $source
                    )
                );
            }

            // Checks logical
            if (false == empty($source = $map_value_anno->source)) {
                if (0 === strpos($source, '$')) {
                    if ('$this' !== $source) {
                        $source_cleaned = substr($source, 1);

                        if (false == property_exists($class, $source_cleaned)) {
                            $this->throwException(
                                __LINE__,
                                $class,
                                sprintf(
                                    '"%s" property can\'t be mapped.'.PHP_EOL
                                    .'"source" parameter isn\'t valid, "%s::$%s" property doesn\'t exists.',
                                    $name,
                                    $class,
                                    $source_cleaned
                                )
                            );
                        }
                    }
                } elseif (false == class_exists($source)) {
                    $this->throwException(
                        __LINE__,
                        $class,
                        sprintf(
                            '"%s" property can\'t be mapped.'.PHP_EOL
                            .'"source" parameter isn\'t valid, "%s" class doesn\'t exists.'.PHP_EOL
                            .'Note: To reference a "%s" property, prefix "source" parameter value with "$" (ex: source="$prop").'.PHP_EOL
                            .'"$this" is allowed too.',
                            $name,
                            $source,
                            $class
                        )
                    );
                } elseif (true == empty($map_value_anno->method)) {
                    $this->throwException(
                        __LINE__,
                        $class,
                        sprintf(
                            '"%s" property can\'t be mapped.'.PHP_EOL
                            .'"method" parameter is missing.'.PHP_EOL
                            .'"method" parameter is required if "source" parameter references a class.',
                            $name
                        )
                    );
                } elseif (false == method_exists($source, $map_value_anno->method)) {
                    $this->throwException(
                        __LINE__,
                        $class,
                        sprintf(
                            '"%s" property can\'t be mapped.'.PHP_EOL
                            .'"source" and/or "method" parameters aren\'t valid'.PHP_EOL
                            .'"%s::%s()" method doesn\'t exists.'.PHP_EOL,
                            $name,
                            $source,
                            $map_value_anno->method
                        )
                    );
                }

                if (false == empty($map_value_anno->method)
                    && false !== strpos($map_value_anno->method, '::')
                ) {
                    $this->throwException(
                        __LINE__,
                        $class,
                        sprintf(
                            '"%s" property can\'t be mapped'.PHP_EOL
                            .'"source" parameter is useless if "method" parameter point directly to a complete static method as "%s"',
                            $name,
                            $map_value_anno->method
                        )
                    );
                }

                $sources[$name] = $source;
            } elseif (false == empty($map_value_anno->method)
                && false === strpos($map_value_anno->method, '::')
            ) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        '"%s" property can\'t be mapped.'.PHP_EOL
                        .'"source" parameter is missing.'.PHP_EOL
                        .'"source" parameter is required if "method" is defined'
                        .' and doesn\'t references a complete static method as "class::method".',
                        $name
                    )
                );
            } elseif (true == empty($map_value_anno->method)
                && false == empty($map_value_anno->property)
            ) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        '"%s" property can\'t be mapped.'.PHP_EOL
                        .'"source" parameter is missing.'.PHP_EOL
                        .'"source" parameter is required if "property" is defined.',
                        $name
                    )
                );
            }

            // Defines property configuration
            $mapping[$name] = [
                'source'   => $map_value_anno->source ?? null,
                'property' => $map_value_anno->property ?? null,
                'method'   => $map_value_anno->method ?? null,
                'args'     => $map_value_anno->args ?? null
            ];
        }

        return [
            'mapping' => $mapping,
            'sources' => $sources
        ];
    }

    /**
     * [isInitialized description]
     *
     * @return bool
     */
    private function isConfigInitialized(string $class): bool
    {
        return $this->configs[$class]['initialized'] ?? false;
    }

    /**
     * [setConfigInitialized description]
     *
     * @param bool $state
     */
    private function setConfigInitialized(string $class, bool $state): self
    {
        $this->configs[$class]['initialized'] = $state;
        return $this;
    }

    /**
     * [getConfig description]
     *
     * @param  string|null $class
     * @return array
     */
    private function getConfig(string $class): array
    {
        $this->configs[$class] = $this->configs[$class] ?? [];

        return $this->configs[$class];
    }

    private function setConfig(string $class, array $config)
    {
        $this->configs[$class] = $config;
    }

    private function getMapping(string $class): array
    {
        return $this->getConfig($class)['mapping'];
    }

    private function getSources(string $class): array
    {
        return $this->getConfig($class)['sources'];
    }

    private function setValueFrom(AbstractEntity $entity, string $destination, ?string $source): self
    {
        // Checks if "$destination" is a mapped property
        if (true == is_null($mapping = $this->getMapping($class = get_class($entity))[$destination] ?? null)) {
            $this->throwException(
                __LINE__,
                $class,
                sprintf(
                    '"%s" call failed.'.PHP_EOL
                    .'"%s::$%s" property is not mapped',
                    __METHOD__,
                    $class,
                    $destination
                )
            );
        }

        $object = $source;
        if (0 === strpos($source ?? '', '$')) {
            if ('$this' === $source) {
                $object = $entity;
            } else {
                $source = substr($source, 1);
                $object = $this->getUnawareVisibility($entity, $source);
                $source_was_updated = $this->sourceWasUpdated($entity, $source);
            }
        }

        // Retrieves value from "method"
        if (false == is_null($mapping['method'])) {
            // Static callback
            if (false !== strpos($mapping['method'], '::')) {
                if (false == is_callable($mapping['method'])) {
                    $exception_message = '"%s" static method is not callable.';
                }

                $source   = null;
                $method   = $mapping['method'];
                $callback = $method;
            } else {
                if (true == is_null($object)) {
                    // Do not change "destination" value if $object is null since the begining
                    if (true == $source_was_updated) {
                        $this->setUnawareVisibility(
                            $entity,
                            $destination,
                            null
                        );
                    }

                    return $this;
                }

                $callback = [$object, $mapping['method']];
                $method = sprintf('%s::%s', $source, $mapping['method']);

                if (false == is_callable($callback)) {
                    $exception_message = '"%s" "method" through "source" is not callable.';
                }
            }

            if (true == isset($exception_message)) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        '"%s" property can\'t be mapped%s.'.PHP_EOL
                        .$exception_message,
                        $destination,
                        $source ? ' with "'.$source.'" "source"' : '',
                        $method
                    )
                );
            }

            $arguments = ($mapping['args'] ?? []) + [
              'object'      => $entity,
              'source'      => $source,
              'destination' => $destination,
              'session'     => $this->pomm->getDefaultSession()
            ];

            try {
                $value = call_user_func($callback, $arguments);
            } catch (Exception $e) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        '"%s" property can\'t be mapped".'.PHP_EOL
                        .'"%s" method call failed. Check "method" parameter.'.PHP_EOL
                        .'Previous exception message :'.PHP_EOL.'%s.',
                        $destination,
                        $source,
                        $method,
                        $e->getMessage()
                    )
                );
            }

            $this->setUnawareVisibility(
                $entity,
                $destination,
                $value
            );

            return $this;
        } elseif (false == is_null($mapping['property'])) {
            if (true == is_null($object)) {
                // Do not change "destination" value if "source" has not changed since the begining
                if (true == $source_was_updated) {
                    $this->setUnawareVisibility(
                        $entity,
                        $destination,
                        null
                    );
                }

                return $this;
            }

            if (false == is_object($object)) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        '"%s" property can\'t be mapped with source "%s".'.PHP_EOL
                        .'"property" attribute is reached from non object.'.PHP_EOL
                        .'"object" expected", "%s" type found for "%s" source.',
                        $destination,
                        $source,
                        gettype($object),
                        $source
                    )
                );
            }

            if (false == property_exists($object, $mapping['property'])) {
                $this->throwException(
                    __LINE__,
                    $class,
                    sprintf(
                        '"%s" property can\'t be mapped with source "%s".'.PHP_EOL
                        .'"%s::$%s" property doesn\'t exists.',
                        $destination,
                        $source,
                        $mapping['property']
                    )
                );
            }

            $this->setUnawareVisibility(
                $entity,
                $destination,
                $this->getUnawareVisibility($object, $mapping['property'])
            );

            return $this;
        }

        $this->setUnawareVisibility(
            $entity,
            $destination,
            $object
        );

        return $this;
    }

    /**
     * Indicates if property has been updated.
     *
     * @param  string $property
     * @return mixed
     */
    private function sourceWasUpdated(AbstractEntity $entity, string $property)
    {
        $entity->mapPropGetSandbox()['state'] = $entity->mapPropGetSandbox()['state'] ?? [];

        $state = $entity->mapPropGetSandbox()['state'][$property] ?? [
            'updated' => false,
            'value'   => $this->getUnawareVisibility($entity, $property)
        ];

        if (true == $state['updated']
            || $state['value'] !== $this->getUnawareVisibility($entity, $property)
        ) {
            $state['updated'] = true;
        }
        $entity->mapPropGetSandbox()['state'][$property] = $state;

        return $state['updated'];
    }

    private function initConfigAnnotations(string $class): array
    {
        $ref = new \ReflectionClass($class);
        $parents = $traits = $properties_anno = [];
        $is_parent_class = false;

        // Use class, trait, parent or parent trait class annotations
        do {
            // Do not retrieves private properties from inherited parent class or their parent class trait
            $ref_properties = $is_parent_class == false
                ? $ref->getProperties()
                : $ref->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

            foreach ($ref_properties as $property) {
                if (true == array_key_exists($name = $property->getName(), $properties_anno)) {
                    continue;
                }

                if ($map_value_anno = $this->reader->getPropertyAnnotation($property, MapValue::class)) {
                    $properties_anno[$name] = $map_value_anno;
                }
            }

            // Retrieves traits, only new ones
            foreach ($ref->getTraits() as $trait) {
                $traits[$trait->getName()] = $trait;
            }

            // Retrieves parent class
            if (true == is_object($parent = $ref->getParentClass())) {
                $parents[$parent->getName()] = $parent;
            };

            // Defines next class to search in.
            // It can be the a new trait.
            if (false == is_object($ref = current($traits))) {
                $is_parent_class = true;
                $ref = current($parents);
                next($parents);
            }

            next($traits);
        } while ($ref);

        return $properties_anno;
    }

    /**
     * Throw & format exception
     *
     * @param  int         $line
     * @param  string      $class
     * @param  string      $message
     * @param  string|null $class_exception
     * @throws \Exception
     */
    private function throwException(int $line, string $class, string $message, string $class_exception = null)
    {
        $this->exception_manager->throw(
            self::class,
            $line,
            sprintf(
                '%s'.PHP_EOL
                .'See "%s" mapping declaration.'.PHP_EOL,
                $message,
                $class
            ),
            $class_exception
        );
    }
}
