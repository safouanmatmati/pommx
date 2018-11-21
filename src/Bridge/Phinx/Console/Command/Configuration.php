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

namespace Pommx\Bridge\Phinx\Console\Command;

use Pommx\Console\Command\Configuration as PommxConfiguration;

class Configuration extends PommxConfiguration
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
        if (false == in_array($type, ($types = ['migrations', 'seeds']))) {
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

        $temp = $configs[$config_name];

        $this->values = [
            'dir' => $temp[$type],
            // Replace {$config_name} wildcard
            'root_directory'] => strtr(
                $temp['psr4']['directory'], ['{$config_name}' => $this->capitalized($config_name)]
            ),
            'root_namespace'] => strtr(
                $temp['psr4']['namespace'], ['{$config_name}' => $this->capitalized($config_name)]
            )
        ];
    }

    /**
     * Returns path file.
     *
     * @param  string $schema
     * @param  string $file_name
     * @return string
     */
    public function getPathFile(string $schema): string
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
}
