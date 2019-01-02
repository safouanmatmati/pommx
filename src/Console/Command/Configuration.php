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

use Pommx\Console\Command\AbstractConfiguration;

class Configuration extends AbstractConfiguration
{
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
            strtr(
                $this->values['root_directory'], ['{$schema}' => $this->capitalized($schema)]
            ),
            ($this->values['directory'] ?? null),
            sprintf("%s%s.php", $this->capitalized($file_name), ($this->values['class_suffix'] ?? ''))
        ];

        return join(
            DIRECTORY_SEPARATOR,
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
        $short_name = (explode('.', substr(strrchr($path_file, DIRECTORY_SEPARATOR), 1)))[0];

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
}
