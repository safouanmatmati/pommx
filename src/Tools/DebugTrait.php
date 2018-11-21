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

namespace Pommx\Tools;

use Pommx\Tools\InheritedReflectionClass;

trait DebugTrait
{
    /**
     * Dump instance informations but hide properties defined on $mask.
     *
     * @param  array $mask
     * @return array
     */
    private function debugInfoMasked(array $mask): array
    {
        $ref_class = new InheritedReflectionClass($this);
        $ref_object = new \ReflectionObject($this);
        $class = $ref_object->getName();

        $ref_class_prop = [];
        foreach ($ref_class->getProperties() as $property) {
            $ref_class_prop[$property->getName()] = $property;
        }

        $ref_object_prop = [];
        foreach ($ref_object->getProperties() as $property) {
            $ref_object_prop[$property->getName()] = $property;
        }

        $properties = $ref_object_prop + $ref_class_prop;

        $data = [];

        foreach ($properties as $property) {
            $representation = $this->getAccessibilityRepresentation($property);
            $name = $property->getName();

            if ($property->getDeclaringClass()->getName() != $class) {
                $representation = "∝" . $representation;
            }

            $key = $representation.$name;

            if (false == isset($data[$key]) && false == in_array($name, $mask)) {
                if (true == $property->isPrivate()) {
                    $property->setAccessible(true);

                    $data[$key] = $property->getValue($this);
                } else {
                    $data[$key] = $this->{$name};
                }
            }
        }

        return $data;
    }

    /**
     * Returns a short string representation of the property accessibility.
     *
     * @param  ReflectionProperty $property
     * @return string
     */
    private function getAccessibilityRepresentation(\ReflectionProperty $property): string
    {
        $representation = '#';

        if (true == $property->isPublic()) {
            $representation = '+';
        } elseif (true == $property->isPrivate()) {
            $representation = '-';
        }

        if (true == $property->isStatic()) {
            $representation .= '::';
        }

        return $representation;
    }
}
