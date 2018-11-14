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

namespace PommX\Console\Command;

class Configuration
{
    /**
     * [private description]
     *
     * @var array
     */
    private $values;

    /**
     * Returns configuration depending for a given target. (entity)
     *
     * @param  string $config_name
     * @param  string $type
     * @return array
     */
    public function __construct(array $default_options, string $config_name, string $type)
    {
        if (false == in_array($type, ($types = ['entity', 'structure', 'repository']))) {
            throw \InvalidArgumentException(
                sprintf(
                    'Failed to retrieve configuration for a given type.'.PHP_EOL
                    .'"%s" as type isn\'t allowed. Types allowed are {"%s"}',
                    $type,
                    joint('", "', $types)
                )
            );
        }

        $filter = function ($value) {
            return false == is_null($value);
        };

        $values = array_intersect_key($default_options, array_flip($this->getAllowed()));
        $values = array_merge(
            $values,
            array_filter($default_options[$type] ?? [], $filter)
        );
        $values = array_merge(
            $values,
            array_filter($default_options['configs'][$config_name] ?? [], $filter)
        );

        // Set 'config_alias' value with 'config-name' argument if option value is equals '{$config_name}'
        if ('{$config_name}' == ($values['config_alias'] ?? '')) {
            $values['config_alias'] = $this->capitalized($config_name);
        }

        $this->values = $values;
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

    private function getAllowed(): array
    {
        return [
            'class_suffix',
            'config_alias',
            'root_ns',
            'dir',
            'parent_class',
            'root_dir',
            'schema_dir'
          ];
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
          ($this->values['root_dir'] ?? null),
          ($this->values['config_alias'] ?? null),
          ($this->values['schema_dir'] ?? null),
          $this->capitalized($schema),
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
          ($this->values['root_ns'] ?? null),
          ($this->values['config_alias'] ?? null),
          ($this->values['schema_dir'] ?? null),
          $this->capitalized($schema),
          ($this->values['dir'] ?? null),
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
