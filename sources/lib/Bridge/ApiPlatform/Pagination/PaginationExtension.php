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

namespace PommX\Bridge\ApiPlatform\Pagination;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use PommX\Repository\Extension\AbstractExtension;
use PommX\Repository\Extension\ExtensionInterface;
use PommX\Repository\QueryBuilder;

use PommX\Bridge\ApiPlatform\Pagination\CursorPaginator\PartialCursorPaginator;
use PommX\Bridge\ApiPlatform\Pagination\CursorPaginator\CursorPaginator;
use PommX\Bridge\ApiPlatform\Pagination\OffsetPaginator\PartialOffsetPaginator;
use PommX\Bridge\ApiPlatform\Pagination\OffsetPaginator\OffsetPaginator;

use PommX\Repository\Extension\Pagination\Pagination;

final class PaginationExtension extends AbstractExtension implements ExtensionInterface
{
    const PARAM_PREFIX = 'api_platform_pagination';

    /**
     * [private description]
     * @var Pagination
     */
    private $pagination_extension;
    /**
     * [private description]
     *
     * @var RequestStack
     */
    private $request_stack;
    /**
     * [private description]
     *
     * @var ResourceMetadataFactoryInterface
     */
    private $resource_metadata_factory;
    private $enabled;
    private $client_enabled;
    private $clientitems_per_page;
    private $items_per_page;
    private $page_parameter_name;
    private $enabled_parameter_name;
    private $items_per_page_parameter_name;
    private $maximum_items_per_page;
    private $partial;
    private $client_partial;
    private $partial_parameter_name;
    private $graphql_custom_cursor;

    public function __construct(
        Pagination $pagination_extension,
        RequestStack $request_stack,
        ResourceMetadataFactoryInterface $resource_metadata_factory,
        bool $enabled = true,
        bool $client_enabled = false,
        bool $clientitems_per_page = false,
        int $items_per_page = 30,
        string $page_parameter_name = 'page',
        string $enabled_parameter_name = 'pagination',
        string $items_per_page_parameter_name = 'itemsPerPage',
        int $maximum_items_per_page = null,
        bool $partial = false,
        bool $client_partial = false,
        string $partial_parameter_name = 'partial',
        bool $graphql_custom_cursor = true
    ) {
        $this->pagination_extension = $pagination_extension;
        $this->request_stack = $request_stack;
        $this->resource_metadata_factory = $resource_metadata_factory;
        $this->enabled = $enabled;
        $this->client_enabled = $client_enabled;
        $this->clientitems_per_page = $clientitems_per_page;
        $this->items_per_page = $items_per_page;
        $this->page_parameter_name = $page_parameter_name;
        $this->enabled_parameter_name = $enabled_parameter_name;
        $this->items_per_page_parameter_name = $items_per_page_parameter_name;
        $this->maximum_items_per_page = $maximum_items_per_page;
        $this->partial = $partial;
        $this->client_partial = $client_partial;
        $this->partial_parameter_name = $partial_parameter_name;
        $this->graphql_custom_cursor = $graphql_custom_cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $query_builder, bool $is_collection, array $context = null): QueryBuilder
    {
        $this->checkNeedle($query_builder->getSql());

        $resource_class = $context['resource_class'] ?? null;
        $operation_name = $context['operation_name'] ?? null;

        if (true == is_null($resource_class)) {
            throw new \InvalidArgumentException('The "$resource_class" parameter must not be null.');
        }

        $request = $this->request_stack->getCurrentRequest();
        if (true == is_null($request)) {
            return $query_builder;
        }

        $resource_metadata = $this->resource_metadata_factory->create($resource_class);

        if (false == $this->isPaginationEnabled($request, $resource_metadata, $operation_name)) {
            return $query_builder;
        }

        $items_per_page = $resource_metadata
            ->getCollectionOperationAttribute(
                $operation_name,
                'pagination_items_per_page',
                $this->items_per_page, true
            );

        if ($request->attributes->get('_graphql')) {
            $iterator_args = $request->attributes->get('_graphql_collections_args', []);
            $items_per_page = $iterator_args[$resource_class]['first'] ?? $items_per_page;
        }

        if ($resource_metadata->getCollectionOperationAttribute($operation_name, 'pagination_client_items_per_page', $this->clientitems_per_page, true)) {
            $maxitems_per_page = $resource_metadata
                ->getCollectionOperationAttribute(
                    $operation_name,
                    'maximum_items_per_page',
                    $this->maximum_items_per_page,
                    true
                );

            $items_per_page = (int) $this->getPaginationParameter(
                $request,
                $this->items_per_page_parameter_name,
                $items_per_page
            );
            $items_per_page = (null !== $maxitems_per_page && $items_per_page >= $maxitems_per_page
                ? $maxitems_per_page
                : $items_per_page
            );
        }

        if (0 > $items_per_page) {
            throw new \InvalidArgumentException('Item per page parameter should not be less than 0');
        }

        $page = $this->getPaginationParameter($request, $this->page_parameter_name, 1);

        if (0 === $items_per_page && 1 < $page) {
            throw new \InvalidArgumentException(
                'Page should not be greater than 1 if itemsPegPage is equal to 0'
            );
        }

        $first_result = ($page - 1) * $items_per_page;

        $this->pagination_extension->setParam($query_builder, 'support', true);

        // Define pagination extension parameters
        $this->pagination_extension->setParam(
            $query_builder,
            'items_per_page',
            $query_builder->getParam('items_per_page') ?? $items_per_page
        );
        $this->pagination_extension->setParam(
            $query_builder,
            'order_direction',
            $query_builder->getParam('order_direction') ?? 'ASC'
        );
        $this->pagination_extension->setParam(
            $query_builder,
            'offset',
            $query_builder->getParam('offset') ?? $first_result
        );

        $this->pagination_extension->setParam(
            $query_builder,
            'is_partial',
            $query_builder->getParam('is_partial') ?? $this->isPartialPaginationEnabled(
                $this->request_stack->getCurrentRequest(),
                $resource_metadata,
                $operation_name
            )
        );

        if ($request->attributes->get('_graphql')) {
            die(__METHOD__);
            $this->pagination_extension->setParam($query_builder, 'use_cursor', true);

            $iterator_args = $request->attributes->get('_graphql_collections_args', []);

            if (isset($iterator_args[$resource_class]['after'])) {
                $this->pagination_extension->setParam($query_builder, 'cursor', $iterator_args[$resource_class]['after']);

                $after = \base64_decode($iterator_args[$resource_class]['after'], true);
                $first_result = (int) $after;
                $first_result = false === $after ? $first_result : ++$first_result;
            }
        }

        $this->pagination_extension->apply($query_builder, $is_collection, $context);

        return $query_builder;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        $resource_class = $context['resource_class'] ?? null;
        $operation_name = $context['collection_operation_name'] ?? null;

        $request = $this->request_stack->getCurrentRequest();

        if (true == is_null($resource_class) || true == is_null($operation_name)
            || false == $is_collection || null === $request
        ) {
            return false;
        }

        $resource_metadata = $this->resource_metadata_factory->create($resource_class);

        if (false == parent::supports($query_builder, $is_collection, $context)) {
            return false;
        }

        return $this->isPaginationEnabled($request, $resource_metadata, $operation_name);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResults(QueryBuilder $query_builder, bool $is_collection, array $context = null): bool
    {
        return $this->pagination_extension->supportsResults($query_builder, $is_collection, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getResults(QueryBuilder $query_builder, bool $is_collection, array $context = null)
    {
        $pager = $this->pagination_extension->getResults($query_builder, $is_collection, $context);

        // Partial
        if (true == $this->pagination_extension->getParam($query_builder, 'is_partial')) {
            // Cursor paginator
            if (true === $this->pagination_extension->getParam($query_builder, 'is_cursor')) {
                return new PartialCursorPaginator($pager);
            }

            // Offset paginator
            return new PartialOffsetPaginator($pager);
        }

        // Cursor paginator
        if (true === $this->pagination_extension->getParam($query_builder, 'is_cursor')) {
            return new CursorPaginator($pager);
        }

        // Offset paginator
        return new OffsetPaginator($pager);
    }

    private function isPartialPaginationEnabled(Request $request = null, ResourceMetadata $resource_metadata = null, string $operation_name = null): bool
    {
        $enabled = $this->partial;
        $client_enabled = $this->client_partial;

        if ($resource_metadata) {
            $enabled = $resource_metadata->getCollectionOperationAttribute($operation_name, 'pagination_partial', $enabled, true);

            if ($request) {
                $client_enabled = $resource_metadata->getCollectionOperationAttribute($operation_name, 'pagination_client_partial', $client_enabled, true);
            }
        }

        if ($client_enabled && $request) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->partial_parameter_name, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    private function isPaginationEnabled(Request $request, ResourceMetadata $resource_metadata, string $operation_name = null): bool
    {
        $enabled = $resource_metadata->getCollectionOperationAttribute($operation_name, 'pagination_enabled', $this->enabled, true);
        $client_enabled = $resource_metadata->getCollectionOperationAttribute($operation_name, 'pagination_client_enabled', $this->client_enabled, true);

        if ($client_enabled) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->enabled_parameter_name, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    private function getPaginationParameter(Request $request, string $parameterName, $default = null)
    {
        if (null !== $paginationAttribute = $request->attributes->get('_api_pagination')) {
            return array_key_exists($parameterName, $paginationAttribute) ? $paginationAttribute[$parameterName] : $default;
        }

        return $request->query->get($parameterName, $default);
    }
}
