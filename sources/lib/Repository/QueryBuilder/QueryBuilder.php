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

namespace PommX\Repository\QueryBuilder;

use PommProject\ModelManager\Model\Projection;
use PommProject\Foundation\ResultIterator;

use PommX\Repository\AbstractRepository;
use PommX\Repository\QueryBuilder\Extension\ExtensionInterface;
use PommX\Repository\QueryBuilder\Extension\ExtensionsManager;
use PommX\Repository\QueryBuilder\Extension\AbstractExtension;

use PommX\Tools\Exception\ExceptionManagerInterface;

class QueryBuilder
{
    /**
     * [private description]
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    /**
     *
     * @var ExtensionManager
     */
    private $extensions_manager;

    /**
     *
     * @var AbstractRepository
     */
    private $repository;

    /**
     *
     * @var Projection
     */
    private $projection;
    /**
     *
     * @var string
     */
    private $sql;

    /**
     *
     * @var array
     */
    private $values;

    /**
     *
     * @var array
     */
    private $params = [];

    /**
     *
     * @var array
     */
    private $context = [];

    /**
     * Create an instance of QueryBuilder.
     *
     * @param Exception         $exception_manager
     * @param ExtensionsManager $extensions_manager
     * @param string            $sql
     * @param array             $values
     * @param Projection|null   $projection
     * @param array|null        $params
     * @param array|null        $context
     */
    public function __construct(
        ExceptionManagerInterface $exception_manager,
        ExtensionsManager $extensions_manager,
        AbstractRepository $repository,
        string $sql,
        array $values,
        Projection $projection = null,
        array $params = null,
        array $context = null
    ) {
        $this->exception_manager  = $exception_manager;
        $this->extensions_manager = $extensions_manager;
        $this->repository         = $repository;
        $this->sql                = $sql;
        $this->values             = $values;
        $this->projection         = $projection;
        $this->params             = $params;
        $this->context            = $context;
    }

    /**
     * Returns repository.
     *
     * @return AbstractRepository [description]
     */
    public function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    /**
     * Edits sql query.
     *
     * @param  string $sql
     * @return self
     */
    public function setSql(string $sql): self
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Returns sql query.
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Edits values that will be used by the sql query.
     *
     * @param  array $values
     * @return self
     */
    public function setValues(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    /**
     * Returns values that will be used by the sql query.
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Edits projection.
     *
     * @param  [type] $projection
     * @return self
     */
    public function setProjection(Projection $projection = null): self
    {
        $this->projectionql = $projection;
        return $this;
    }

    /**
     * Add a specific parameter.
     * If it already exists, it can be replace through "$replace".
     *
     * @param  string    $key
     * @param  mixed     $value
     * @param  bool|null $replace
     * @return self
     */
    public function addParam(string $key, $value, bool $replace = null): self
    {
        if (false !== $replace) {
            $this->params[$key] = $value;
        }
        return $this;
    }

    /**
     * Returns a specific parameter.
     *
     * @param  string $key
     * @return mixed|null
     */
    public function getParam(string $key)
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Returns stored parameters.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Returns stored context.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Adds an array of parameters.
     * If a parameter exists, it can be replace through "$replace".
     *
     * @param  array     $params
     * @param  bool|null $replace
     * @return self
     */
    public function addParams(array $params, bool $replace = null): self
    {
        if (false !== $replace) {
            $this->params = $params + $this->params;
            return $this;
        }

        $this->params = $this->params + $params;

        return $this;
    }

    /**
     * Returns projection.
     *
     * @return Projection|null
     */
    public function getProjection(): ?Projection
    {
        return $this->projection;
    }

    /**
     * Execute query and return results.
     *
     * @return ResultIterator
     */
    public function execute(): ResultIterator
    {
        $sql = AbstractExtension::removeExtensionsNeedle($this->sql);

        return $this->getRepository()
            ->query(
                $sql,
                $this->values,
                $this->projection,
                $this->params
            );
    }

    /**
     * Apply specific extension on query builder.
     * Return QueryBuilder or results if one of the applied extensions support results return
     * and its asked through "$get_results".
     *
     * @param  string     $name
     * @param  bool       $is_collection
     * @param  bool       $get_results
     * @param  array|null $context
     * @return self|ResultIterator
     */
    public function applyExtension(
        string $name,
        bool $is_collection,
        bool $get_results,
        array $context = null
    ) {
        $extension = $this->extension_manager
            ->getExtensions($name);

        if (true == is_array($result = $this->applySupportedExtension($extension, $is_collection, $context))) {
            return current($result);
        }

        if (false == $get_results) {
            return $this;
        }

        $result = $this->execute();

        if (false == $is_collection) {
            return $result->current();
        }

        return $result;
    }

    /**
     * Apply extensions on query builder.
     * Return QueryBuilder or results if one of the applied extensions support results return
     * and its asked through "$get_results".
     *
     * @param  bool       $is_collection
     * @param  bool       $get_results
     * @param  array|null $context
     * @return self|ResultIterator
     */
    public function applyExtensions(
        bool $is_collection,
        bool $get_results,
        array $context = null
    ) {
        foreach ($this->extensions_manager->getExtensions() as $extension) {
            $result = $this->applySupportedExtension(
                $extension,
                $is_collection,
                $get_results,
                $context
            );

            if (true == is_array($result)) {
                return current($result);
            }
        }

        if (false == $get_results) {
            return $this;
        }

        $result = $this->execute();

        if (false == $is_collection) {
            return $result->current();
        }

        return $result;
    }

    /**
     *  Edit query builder if supported and return result if asked and extension support it.
     *
     * @param  ExtensionInterface $extension
     * @param  bool               $is_collection
     * @param  bool               $get_results
     * @param  array              $context
     * @return array
     */
    private function applySupportedExtension(
        ExtensionInterface $extension,
        bool $is_collection,
        bool $get_results,
        array $context = null
    ): ?array {
        // Extension allow to edit QueryBuilder
        if (true == $extension->supports($this, $is_collection, $context)) {
            $extension->apply($this, $is_collection, $context);

            // Results expected and extension can return them
            if (true == $get_results
                && true == $extension->supportsResults($this, $is_collection, $context)
            ) {
                return [$extension->getResults($this, $is_collection, $context)];
            }
        }

        return null;
    }

    public function getOne()
    {
        return $this->applyExtensions(false, true, $this->context);
    }

    public function getAll()
    {
        return $this->applyExtensions(true, true, $this->context);
    }
}
