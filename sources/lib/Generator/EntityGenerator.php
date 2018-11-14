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

namespace PommX\Generator;

use PommProject\ModelManager\Generator\EntityGenerator as PommEntityGenerator;
use PommProject\Foundation\ParameterHolder;

class EntityGenerator extends PommEntityGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generate(ParameterHolder $input, array $output = [])
    {
        $parameters = [
            'entity'             => $input->getParameter('entity'),
            'namespace'          => trim($this->namespace, '\\'),
            'schema'             => $this->schema,
            'relation'           => $this->relation,
            'parent_class'       => '',
            'parent_class_alias' => ''
        ];

        if (false == is_null($parent_class = $input->getParameter('parent_class'))) {
            $parameters['parent_class'] = sprintf(
                '%suse %s;%s',
                PHP_EOL,
                $parent_class,
                PHP_EOL
            );

            $parameters['parent_class_alias'] = sprintf(
                ' extends %s',
                substr(strrchr($parent_class, "\\"), 1)
            );
        }

        $this
            ->checkOverwrite($input)
            ->outputFileCreation($output)
            ->saveFile(
                $this->filename,
                $this->mergeTemplate($parameters)
            );

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeTemplate()
    {
        return <<<'_'
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

namespace {:namespace:};
{:parent_class:}
/**
 * {:entity:} entity.
 *
 * Entity class for {{:schema:}.{:relation:}}.
 */
class {:entity:}{:parent_class_alias:}
{

}

_;
    }
}
