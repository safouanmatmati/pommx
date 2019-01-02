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

namespace Pommx\Generator;

use PommProject\ModelManager\Generator\StructureGenerator as PommStructureGenerator;
use PommProject\Foundation\ParameterHolder;
use PommProject\ModelManager\Exception\GeneratorException;

class StructureGenerator extends PommStructureGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generate(ParameterHolder $input, array $output = [])
    {
        $table_oid         = self::checkRelationInformation();
        $field_information = $this->getFieldInformation($table_oid);
        $table_comment     = $this->getTableComment($table_oid);
        $add_fields        = $this->formatAddFields($field_information);
        $entity            = $input->getParameter('entity');
        $array_to_string   = [
            'primary_key' => $this->getPrimaryKey($table_oid),
            'foreign_key' => $this->getForeignKey($table_oid),
            'not_null'    => $this->getNotNull($table_oid),
            'type_enum'   => $this->getTypeEnum($table_oid)
        ];

        foreach ($array_to_string as $name => $array) {
            $array_to_string[$name] = $this->arrayToString($array);
        }

        if ($table_comment === null) {
            $table_comment = <<<TEXT

Class and fields comments are inspected from table and fields comments. Just add comments in your database and they will appear here.
@see http://www.postgresql.org/docs/9.0/static/sql-comment.html
TEXT;
        }

        $parameters = [
            'entity'             => $entity,
            'schema'             => $this->schema,
            'namespace'          => $this->namespace,
            'relation'           => $this->relation,
            'table_comment'      => $this->createPhpDocBlockFromText($table_comment),
            'fields_comment'     => $this->formatFieldsComment($field_information),
            'parent_class'       => '',
            'parent_class_alias' => '',
            'primary_key'        => $array_to_string['primary_key'],
            'foreign_key'        => $array_to_string['foreign_key'],
            'not_null'           => $array_to_string['not_null'],
            'type_enum'          => $array_to_string['type_enum'],
            'add_fields'         => $add_fields
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
     * Convert array to string.
     *
     * @param  array $array [description]
     * @return string        [description]
     */
    private function arrayToString(array $array): string
    {
        return join(
            ', ',
            array_map(
                function ($key, $val) {
                    if (true == is_array($val)) {
                        $val = sprintf('[%s]', $this->arrayToString($val));

                        return sprintf("'%s' => %s", $key, $val);
                    }

                    if (true == is_int($key)) {
                        return sprintf("'%s'", $val);
                    }

                    return sprintf("'%s' => '%s'", $key, $val);
                },
                array_keys($array),
                $array
            )
        );
    }

    /**
     * checkRelationInformation
     *
     * Check if the given schema and relation exist. If so, the table oid is
     * returned, otherwise a GeneratorException is thrown.
     *
     * @copyright 2014 - 2015 Grégoire HUBERT
     * @author    Grégoire HUBERT
     * @throws    GeneratorException
     * @return    int $oid
     */
    private function checkRelationInformation()
    {
        if ($this->getInspector()->getSchemaOid($this->schema) === null) {
            throw new GeneratorException(sprintf("Schema '%s' not found.", $this->schema));
        }

        $table_oid = $this->getInspector()->getTableOid($this->schema, $this->relation);

        if ($table_oid === null) {
            throw new GeneratorException(
                sprintf(
                    "Relation '%s' could not be found in schema '%s'.",
                    $this->relation,
                    $this->schema
                )
            );
        }

        return $table_oid;
    }

    /**
     * getForeignKey
     *
     * Returnd foreign key list.
     *
     * @param  int $table_oid [description]
     * @return array            [description]
     */
    protected function getForeignKey(int $table_oid): array
    {
        return $this
            ->getInspector()
            ->getForeignKey($table_oid);
    }

    /**
     * Returns fields lists and indicates if they are defined as "not null".
     *
     * @param  int $table_oid [description]
     * @return array            [description]
     */
    protected function getNotNull(int $table_oid): array
    {
        return $this
            ->getInspector()
            ->getNotNull($table_oid);
    }

    /**
     * Returns enum fields and their choices.
     *
     * @param  int $table_oid [description]
     * @return array            [description]
     */
    protected function getTypeEnum(int $table_oid): array
    {
        return $this
            ->getInspector()
            ->getTypeEnum($table_oid);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeTemplate()
    {
        return <<<'_'
<?php

/*
 * This file was generated from Pommx package.
 */

declare(strict_types=1);

namespace {:namespace:};
{:parent_class:}
/**
 * {:entity:} structure.
 *
 * Structure class for {{:schema:}.{:relation:}}.
{:table_comment:}
 *
{:fields_comment:}
 */
class {:entity:}{:parent_class_alias:}
{
    /**
     * __construct
     *
     * Structure definition.
     *
     * @access public
     */
    public function __construct()
    {
        $this
            ->setRelation('{:schema:}.{:relation:}')
            ->setPrimaryKey([{:primary_key:}])
            ->setForeignKey([{:foreign_key:}])
            ->setNotNull([{:not_null:}])
            ->setTypeEnum([{:type_enum:}])
{:add_fields:}
            ;
    }
}

_;
    }
}
