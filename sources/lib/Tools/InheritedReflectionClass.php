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

namespace PommX\Tools;

/**
 * InheritedReflectionClass.
 *
 * As {@see \ReflectionClass}, returns class informations by adding thoses of its parents and traits.
 */
class InheritedReflectionClass extends \ReflectionClass
{
    /**
     * [protected description]
     *
     * @var array[]
     */
    protected static $classes = [];

    /**
     * [__construct description]
     *
     * @param object|string $object_or_class
     */
    public function __construct($object_or_class)
    {
        parent::__construct($object_or_class);

        if (false == array_key_exists($this->getName(), self::$classes)) {
            self::initClass($this);
        }
    }

    /**
     * Loads, if not yet done, and returns all data from a reflected class.
     *
     * @param  self $ref
     * @return array
     */
    private static function initClass(self $ref): array
    {
        if (false == isset(self::$classes[$class = $ref->getName()])) {
            self::loadInheritedData(new \ReflectionClass($class));
        }

        return self::$classes[$class];
    }

    /**
     * Returns all data from of a loaded class.
     *
     * @param  string $class
     * @return array
     */
    private function &getClass(string $class): array
    {
        return self::$classes[$class];
    }

    /**
      * Returns properties of current reflected class, its inherited parents and traits.
     *
     * @param  [type] $filters
     * @return array
     */
    public function getProperties($filters = null)
    {
        return $this->getInheritedProperties($filters);
    }

    /**
     * Returns traits used from current reflected class, its inherited parents and traits.
     *
     * @return array
     */
    public function getTraits()
    {
        return self::getClass($this->getName())['traits'];
    }

    /**
     * Indicates if a trait is used from current reflected class, its inherited parents or traits.
     *
     * @param  string $trait
     * @return bool
     */
    public function hasTrait(string $trait): bool
    {
        return array_key_exists($trait, self::getClass($this->getName())['traits']);
    }

    /**
     * Returns properties from current reflected class, its inherited parents and traits.
     *
     * @param  [type] $filters
     * @return array
     */
    protected function getInheritedProperties(int $filters = null): array
    {
        $class_properties = &self::getClass($this->getName())['properties'];

        if (false == is_null($filters)) {
            $is_static_allowed = (bool) ($filters & \ReflectionProperty::IS_STATIC);
            $is_public_allowed = (bool) ($filters & \ReflectionProperty::IS_PUBLIC);
            $is_protected_allowed = (bool) ($filters & \ReflectionProperty::IS_PROTECTED);
            $is_private_allowed = (bool) ($filters & \ReflectionProperty::IS_PRIVATE);
            $is_pri_inherit_allow = (bool) ($filters & self::IS_PRIVATE_INHERITED);

            foreach ($class_properties as $property) {
                if (false == $is_static_allowed && true == $property->isStatic()) {
                    unset($class_properties[$property->getName()]);
                    continue;
                }
                if (false == $is_public_allowed && true == $property->isPublic()) {
                    unset($class_properties[$property->getName()]);
                } elseif (false == $is_protected_allowed && true == $property->isProtected()) {
                    unset($class_properties[$property->getName()]);
                } elseif (false == $is_private_allowed && true == $property->isPrivate()) {
                    unset($class_properties[$property->getName()]);
                } elseif (false == $is_pri_inherit_allow
                    && true == $property->isPrivate()
                    && $this->getName() !== $property->getDeclaringClass()->getName()
                ) {
                    unset($class_properties[$property->getName()]);
                }
            }
        }

        return $class_properties;
    }

    /**
     * Loads & returns data from reflected class and its inherited parents and traits.
     *
     * @param  ReflectionClass $ref
     * @return array
     */
    private static function loadInheritedData(\ReflectionClass $ref): array
    {
        $class = $source_class = $ref->getName();
        $is_parent_class = false;
        $traits = $parents = $data = $classes_founded = [];

        $classes_founded[$class] = [
            'parent_class' => null,
            'properties' => [],
            'traits' => []
        ];

        // Collect from traits, traits of trait, parents and parent traits
        do {
            if (true == $is_parent_class) {
                $class = $ref->getName();

                if (false == is_null(self::$classes[$class] ?? null)) {
                    $classes_founded[$class] = self::$classes[$class];
                    break;
                };

                $classes_founded[$class] = [
                    'parent_class' => null,
                    'properties' => [],
                    'traits' => []
                ];
            }

            $properties = $ref->getProperties();

            foreach ($properties as $property) {
                if (true == isset($classes_founded[$class]['properties'][$property->getName()])) {
                    continue;
                }

                $classes_founded[$class]['properties'][$property->getName()] = $property;
            }

            // Add only new traits
            foreach ($ref->getTraits() as $trait) {
                $traits[$trait->getName()] = $trait;
                $classes_founded[$class]['traits'][$trait->getName()] = $trait;
            }

            // Retrieves parent class
            if (true == is_object($parent = $ref->getParentClass())) {
                $classes_founded[$class]['parent_class'] = $parent->getName();
                $parents[$parent->getName()] = $parent;
            };

            // Defines next class to search in.
            // It can be the a new trait.
            if (true == is_object($ref = current($traits))) {
                $is_parent_class = false;
            } else {
                $is_parent_class = true;
                $ref = current($parents);
                next($parents);
            }

            next($traits);
        } while ($ref);

        $classes_founded = array_reverse($classes_founded);
        $prev = [];

        while ($data = current($classes_founded)) {
            $class_data = [
                'parent_class' => $data['parent_class'],
                'properties' => $data['properties'] + ($prev['properties'] ?? []),
                'traits' => $data['traits'] + ($prev['traits'] ?? []),
            ];

            self::$classes[key($classes_founded)] = $class_data;

            $prev = $class_data;

            next($classes_founded);
        }

        return self::$classes[$source_class];
    }
}
