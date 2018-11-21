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

use Pommx\Tools\Exception\ExceptionManagerInterface;

trait CheckIntegrityTrait
{
    /**
     * Checks value integrity.
     *
     * @param  string                    $name
     * @param  mixed                     $value
     * @param  array                     $param_types_def
     * @param  ExceptionManagerInterface $exception_manager
     * @throws \InvalidArgumentException
     */
    private static function checkIntegrity(
        string $name,
        $value,
        array $param_types_def,
        ExceptionManagerInterface $exception_manager
    ): void {
        if (true == in_array($type = gettype($value), $param_types_def)) {
            return ;
        }

        // "bool" alias
        if ('boolean' == $type && true == in_array('bool', $param_types_def)) {
            return ;
        }

        // "int" alias
        if ('integer' == $type && true == in_array('int', $param_types_def)) {
            return ;
        }

        // Enum
        if ('string' == $type && true == isset($param_types_def['enum'])
            && false == in_array($value, $param_types_def['enum'])
        ) {
            $exception_manager->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Parameter "%s" is not valid.' .PHP_EOL
                    . '"%s" value not allowed, ["%s"] expected.',
                    $name,
                    $value,
                    implode('", "', $param_types_def['enum'])
                )
            );
        } else {
            unset($param_types_def['enum']);
        }

        // Callable
        if ('array' == $type && true == in_array('callable', $param_types_def) && true == is_callable($value)) {
            return;
        }

        if ('object' == $type) {
            // Callable
            if (true == in_array('callable', $param_types_def) && true == is_callable($value)) {
                return;
                // Class
            } elseif (true == array_key_exists('class', $param_types_def)
                && true == is_a($value, $param_types_def['class'])
            ) {
                return;
            }
        }

        $exception_manager->throw(
            __TRAIT__,
            __LINE__,
            sprintf(
                'Parameter "%s" is not valid.' .PHP_EOL
                . '"%s" type found, ["%s"] expected.',
                $name,
                $type,
                implode('", "', $param_types_def)
            )
        );
    }

    /**
     * Checks array associative integrity.
     *
     * $params_definitions format could be as :
     *  - 'key_1' => [{option} => ['key_2', 'key_3']],
     *  - 'key_2' => 'key_1'
     *
     * Available {option} are :
     *  - "one" =  only one expected and required,
     *  - "at_least" = at least one expected
     *  - "max_one" = one maximum
     *  - "all" = all required (default)
     *
     * @param  array                     $params
     * @param  array                     $params_definitions
     * @param  ExceptionManagerInterface $exception_manager
     * @throws \InvalidArgumentException
     */
    private static function checkArrayAssocDependencies(
        array $params,
        array $params_definitions,
        ExceptionManagerInterface $exception_manager
    ): void {
        // Test $params_definitions
        try {
            self::checkArrayIntegrity(
                $params_definitions,
                ['array', 'string'],
                true,
                $exception_manager
            );
        } catch (Exception $e) {
            $exception_manager->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Failed to check array associative dependencies.'.PHP_EOL
                    .'"$params_definitions" argument is not valid.'
                ),
                null,
                $e
            );
        }

        foreach ($params_definitions as $left => $right) {
            if (false == isset($params[$left])) {
                continue;
            }

            if (true == is_array($right)) {
                $rule   = key($right);
                $fields = current($right);

                $intersect = array_intersect_key($params, array_flip($fields));
                $message = null;

                switch ($rule) {
                case 'one':
                    if (1 !== count($intersect)) {
                        $message = sprintf(
                            'Number of parameters is not valid.' .PHP_EOL
                            .'One, and only one, of those parameters {"%s"} parameter has be to defined'.PHP_EOL
                            .' when "%s" parameter is defined.',
                            join('", "', $fields),
                            $left
                        );
                    }
                    break;
                case 'at_least':
                    if (0 === count($intersect)) {
                        $message = sprintf(
                            'Number of parameters is not valid.' .PHP_EOL
                            .'At least one of those parameters {"%s"} parameter has be to defined'.PHP_EOL
                            .' when "%s" parameter is defined.',
                            join('", "', $fields),
                            $left
                        );
                    }
                    break;
                case 'max_one':
                    if (1 < count($intersect)) {
                        $message = sprintf(
                            'Number of parameters is not valid.' .PHP_EOL
                            .'Only one of those parameters {"%s"} parameter can be to defined'.PHP_EOL
                            .' when "%s" parameter is defined.',
                            join('", "', $fields),
                            $left
                        );
                    }
                    break;
                case 'all':
                case is_int($rule):
                    if (count($fields) !== count($intersect)) {
                        $message = sprintf(
                            'Number of parameters is not valid.' .PHP_EOL
                            .'All of those parameters {"%s"} parameter has be to defined'.PHP_EOL
                            .' when "%s" parameter is defined.',
                            join('", "', $fields),
                            $left
                        );
                    }
                    break;
                default:
                    $message = sprintf(
                        'Failed to check dependencies.' .PHP_EOL
                        . '"%s" is not a valid rule.'.PHP_EOL
                        .'Allowed rules are {"one", "max_one", "at_least", "all"}.',
                        $rule
                    );
                    break;
                }

                if (false == is_null($message)) {
                    $exception_manager->throw(
                        __TRAIT__,
                        __LINE__,
                        $message
                    );
                }

                continue;
            }

            if (false == isset($params[$right])) {
                $exception_manager->throw(
                    __TRAIT__,
                    __LINE__,
                    sprintf(
                        'Parameter "%s" is missing.' .PHP_EOL
                        . '"%s" parameter has be to defined when "%s" is defined.',
                        $right,
                        $right,
                        $left
                    )
                );
            }
        }
    }

    /**
     * Checks array associative integrity.
     *
     * @param  array                     $params
     * @param  array                     $params_definitions
     * @param  ExceptionManagerInterface $exception_manager
     * @param  string                    $real_trigger_class
     * @param  int                       $class_line_call
     * @throws \InvalidArgumentException
     */
    private static function checkArrayAssocIntegrity(
        array $params,
        array $params_definitions,
        ExceptionManagerInterface $exception_manager
    ): void {
        if (false == empty($diff = array_diff_key($params, $params_definitions))) {
            $exception_manager->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    '["%s"] is/are not allowed parameter(s).' .PHP_EOL
                    . 'Allowed parameters are ["%s"].',
                    implode('", "', array_keys($diff)),
                    implode('", "', array_keys($params_definitions))
                )
            );
        }

        foreach ($params_definitions as $param => $types) {
            self::checkIntegrity(
                $param,
                $params[$param] ?? null,
                $types,
                $exception_manager
            );
        }
    }

    /**
     * Checks array integrity.
     *
     * @param  array                     $params
     * @param  array                     $params_definitions
     * @param  ExceptionManagerInterface $exception_manager
     * @param  string                    $real_trigger_class
     * @param  int                       $class_line_call
     * @throws \InvalidArgumentException
     */
    private static function checkArrayIntegrity(
        ?array $params,
        array $params_definitions,
        bool $is_required,
        ExceptionManagerInterface $exception_manager
    ): void {
        if (true == $is_required && true == empty($params)) {
            $exception_manager->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Array is empty.' .PHP_EOL
                    . 'It has to be defined.'
                )
            );
        }

        if (true == is_null($params)) {
            return;
        }

        // Test $params_definitions
        $self_params_def = [
            'key'   => ['array', 'NULL'],
            'value' => ['array', 'NULL'],
            'count' => ['int', 'NULL']
        ];

        // Check values types only
        if (true == empty(array_intersect_key($self_params_def, $params_definitions))) {
            foreach ($params as $param => $value) {
                self::checkIntegrity(
                    $param,
                    $value,
                    $params_definitions,
                    $exception_manager
                );
            }

            return;
        }

        try {
            self::checkArrayAssocIntegrity(
                $params_definitions,
                $self_params_def,
                $exception_manager
            );
        } catch (Exception $e) {
            $exception_manager->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Failed to check array integrity.'.PHP_EOL
                    .'"$params_definitions" argument is not valid.'
                ),
                null,
                $e
            );
        }

        if (true == isset($params_definitions['count'])
            && ($count = (count($params)) != $params_definitions['count'])
        ) {
            $exception_manager->throw(
                __TRAIT__,
                __LINE__,
                sprintf(
                    'Array count invalid.' .PHP_EOL
                    . '%d element expected, %d found.',
                    $count,
                    $params_definitions['count']
                )
            );
        }

        foreach ($params as $param => $value) {
            if (true == isset($params_definitions['key'])) {
                self::checkIntegrity(
                    $param,
                    $param,
                    $params_definitions['key'],
                    $exception_manager
                );
            }

            if (true == isset($params_definitions['value'])) {
                self::checkIntegrity(
                    $param,
                    $value,
                    $params_definitions['value'],
                    $exception_manager
                );
            }
        }
    }
}
