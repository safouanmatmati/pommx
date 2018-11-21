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

/**
 * Provides a getter, setter and caller methods that works with privates property/methods.
 *
 * @var trait
 */
trait UnawareVisibilityTrait
{
    /**
     * [private description]
     *
     * @var callable[]
     */
    private static $unaware_functions;

    /**
     * Set property.
     *
     * @param $object $object
     * @param string $property
     * @param mixed  $value
     */
    private function setUnawareVisibility($object, string $property, $value)
    {
        try {
            $object->{$property} = $value;
        } catch (\Error $e) {
            if (true == is_null(self::$unaware_functions['setter'] ?? null)) {
                self::$unaware_functions['setter'] = function ($property, $value) {
                    $this->{$property} = $value;
                };
            }

            self::$unaware_functions['setter']->call($object, $property, $value);
        }
    }

    /**
     * Returns property value.
     *
     * @param  $object $object
     * @param  string $property
     * @return mixed
     */
    private function getUnawareVisibility($object, string $property)
    {
        try {
            return $object->{$property};
        } catch (\Error $e) {
            if (true == is_null(self::$unaware_functions['getter'] ?? null)) {
                self::$unaware_functions['getter'] = function ($property) {
                    return $this->{$property};
                };
            }

            return self::$unaware_functions['getter']->call($object, $property);
        }
    }

    /**
     * Calls $object method.
     *
     * @param  object $object
     * @param  string $method
     * @param  [...] $arguments
     * @return mixed
     */
    private function callUnawareVisibility($object, string $method, ... $arguments)
    {
        try {
            return $object->{$method}(... $arguments);
        } catch (\Error $e) {
            if (true == method_exists($object, $method)) {
                $function = function (... $arguments) use ($method) {
                    $this->{$method}(... $arguments);
                };

                return $function->call($object, ... $arguments);
            }

            throw $e;
        }
    }
}
