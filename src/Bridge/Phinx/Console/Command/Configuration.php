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
    public function getDirPath(string $schema): string
    {
        $elements = [
            // Replace {$schema} wildcard
            strtr($this->values['root_directory'], ['{$schema}' => $this->capitalized($schema)]),
            ($this->values['directory'] ?? null)
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
}
