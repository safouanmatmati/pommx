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

class Configuration
{
    /**
     * [private description]
     *
     * @var array
     */
    private $values;

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
        if (false == in_array($type, ($types = ['entity', 'structure', 'repository']))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Failed to retrieve configuration for a given type.'.PHP_EOL
                    .'"%s" as type isn\'t allowed. Types allowed are {"%s"}',
                    $type,
                    join('", "', $types)
                )
            );
        }

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

        $temp = $configs[$config_name][$type];

        // Replace {$config_name} wildcard
        $temp['root_directory'] = strtr(
            $temp['psr4']['directory'], ['{$config_name}' => $this->capitalized($config_name)]
        );

        $temp['root_namespace'] = strtr(
            $temp['psr4']['namespace'], ['{$config_name}' => $this->capitalized($config_name)]
        );

        $this->values = $temp;
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
     * Returns path file.
     *
     * @param  string $schema
     * @param  string $file_name
     * @return string
     */
    public function getPathFile(string $schema, string $file_name): string
    {
        $elements = [
            // Replace {$schema} wildcard
            $this->values['root_directory'], ['{$schema}' => $this->capitalized($schema)]
            ($this->values['dir'] ?? null),
            sprintf("%s%s.php", $this->capitalized($file_name), ($this->values['class_suffix'] ?? ''))
        ];

        return join(
            '/',
            array_filter(
                $elements,
                function ($val) {
                    return false == empty($val);
                }
            )
        );
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
                $this->values['root_namespace'], ['{$schema}' => $this->capitalized($schema)]
            ),
            ($this->values['dir'] ?? null)
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
     * Returns file informations depending on configuration.
     *
     * @param  string $schema
     * @param  string $file_name
     * @return array
     */
    public function getFileInfos(string $schema, string $file_name): array
    {
        $namespace = $this->getNamespace($schema);
        $path_file = $this->getPathFile($schema, $file_name);

        // Retrieves file name then removes file extension
        $short_name = (explode('.', substr(strrchr($path_file, '/'), 1)))[0];

        $name = sprintf(
            '%s\\%s',
            $namespace,
            $short_name
        );

        return [
          'path_file'  => $path_file,
          'namespace'  => $namespace,
          'name'       => $name,
          'short_name' => $short_name
        ];
    }

    /**
     * Return string with first letter capitalized.
     *
     * @param  string $string [description]
     * @return string         [description]
     */
    private function capitalized(string $string): string
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
