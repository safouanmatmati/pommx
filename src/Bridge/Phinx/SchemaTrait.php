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

namespace Pommx\Bridge\Phinx;

trait SchemaTrait
{
    /**
     * Returns list of schemas names allowed.
     *
     * @var string[]
     */
    abstract protected function getSchemasNames(): array;

    /**
     * Change current schema and create it if it doesn't exisits.
     *
     * @param string $name
     */
    protected function useSchema(string $name): void
    {
        $this->setSchema($name);

        // Create schema if doesn't exists
        $this->getAdapter()->createSchemaTable();
    }

    /**
     * Change current schema.
     *
     * @param string $name
     */
    protected function setSchema(string $name): void
    {
        $this->checkSchema($name);

        $this->getAdapter()->setOptions(
            array_replace($this->getAdapter()->getOptions(), ['schema' => $name])
        );
    }

    /**
     * Checks schema name validity.
     *
     * @param  string $name
     * @throws \LogicException type
     */
    protected function checkSchema(string $name): void
    {
        if (false == in_array($name, $this->getSchemasNames())) {
            throw new \LogicException(
                sprintf(
                    '"%s" is not a valid schema.'.PHP_EOL
                    . 'Only {"%s"} schemas are allowed.',
                    $name,
                    implode('", "', $this->getSchemasNames())
                )
            );
        }
    }

    /**
     * Returns current schema name.
     *
     * @return string
     */
    public function getSchemaName(): string
    {
        $options = $this->getAdapter()->getOptions();

        return empty($options['schema']) ? 'public' : $options['schema'];
    }
}
