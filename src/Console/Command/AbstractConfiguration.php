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

namespace Pommx\Console\Command;

abstract class AbstractConfiguration
{
    /**
     * [private description]
     *
     * @var array
     */
    protected $values;

    /**
     * Returns configuration depending on a given type.
     *
     * @param  array  $configs
     * @param  string $config_name
     * @param  string $type
     * @return array
     */
    public function __construct(array $configs, string $config_name, string $type)
    {
        if (false == array_key_exists($config_name, $configs)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" Pommx session doesn\'t exists.'.PHP_EOL
                    .'Existing sessions are {"%s"}',
                    $config_name,
                    join('", "', array_keys($configs))
                )
            );
        }

        if (false == array_key_exists($type, $configs[$config_name])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Failed to retrieve configuration for a given type.'.PHP_EOL
                    .'"%s" as type isn\'t allowed. Types allowed are {"%s"}',
                    $type,
                    join('", "', array_keys($configs[$config_name]))
                )
            );
        }

        $this->initValues(
            // Replace {$config_name} wildcard
            strtr(
                $configs[$config_name]['psr4']['directory'],
                ['{$config_name}' => $this->capitalized($config_name)]
            ),
            // Replace {$config_name} wildcard
            strtr(
                $configs[$config_name]['psr4']['namespace'],
                ['{$config_name}' => $this->capitalized($config_name)]
            ),
            $configs[$config_name][$type]
        );
    }

    protected function initValues(string $root_directory, string $root_namespace, array $data)
    {
        $this->values = $data;
        $this->values['root_directory'] = $root_directory;
        $this->values['root_namespace'] = $root_namespace;
    }

    /**
     * [getValues description]
     *
     * @return string|null [description]
     */
    public function getValue(string $key): ?string
    {
        return $this->values[$key];
    }

    /**
     * Returns namespace depending on configuration.
     *
     * @param  string $schema
     * @return string
     */
    public function getNamespace(string $schema): string
    {
        $elements = [
            // Replace {$schema} wildcard
            strtr(
                $this->values['root_namespace'],
                ['{$schema}' => $this->capitalized($schema)]
            ),
            ($this->values['directory'] ?? null)
        ];

        return join(
            '\\',
            array_filter(
                $elements,
                function ($val) {
                    return false == empty($val);
                }
            )
        );
    }

    /**
     * Return string with first letter capitalized.
     *
     * @param  string $string [description]
     * @return string         [description]
     */
    protected function capitalized(string $string): string
    {
        return preg_replace_callback(
            '/_([a-z])/',
            function ($v) {
                return ucfirst($v[1]);
            },
            ucfirst($string)
        );
    }
}
