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

use PommProject\ModelManager\Generator\ModelGenerator;
use PommProject\Foundation\ParameterHolder;
use PommProject\Foundation\Where;
use PommProject\ModelManager\Exception\GeneratorException;

class RepositoryGenerator extends ModelGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generate(ParameterHolder $input, array $output = [])
    {
        $schema_oid = $this
            ->getSession()
            ->getInspector()
            ->getSchemaOid($this->schema);

        if ($schema_oid === null) {
            throw new GeneratorException(sprintf("Schema '%s' does not exist.", $this->schema));
        }

        $relations_info = $this
            ->getSession()
            ->getInspector()
            ->getSchemaRelations($schema_oid, new Where('cl.relname = $*', [$this->relation]))
            ;

        if ($relations_info->isEmpty()) {
            throw new GeneratorException(sprintf("Relation '%s.%s' does not exist.", $this->schema, $this->relation));
        }

        $namespace  = trim($this->namespace, '\\');
        $entity     = $input->getParameter('entity');
        $parameters = [
            'entity'             => $entity,
            'namespace'          => $namespace,
            'schema'             => $this->schema,
            'relation'           => $this->relation,
            'relation_type'      => $relations_info->current()['type'],
            'structure_class'    => $input->getParameter('structure_class'),
            'entity_class'       => $input->getParameter('entity_class'),
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
use {:structure_class:} as {:entity:}Structure;
use {:entity_class:} as {:entity:}Entity;

/**
 * {:entity:} repository.
 *
 * Repository class for {:relation_type:} {{:schema:}.{:relation:}}.
 */
class {:entity:}{:parent_class_alias:}
{
    /**
     * __construct()
     *
     * Repository constructor.
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct(new {:entity:}Structure(), {:entity:}Entity::class);
    }
}

_;
    }
}
