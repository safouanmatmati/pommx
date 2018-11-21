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

namespace Pommx\Bridge\ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;

use PommProject\Foundation\Pomm;

use Pommx\Entity\AbstractEntity;

/**
 * Defines some metadata from entity structure definition.
 */
final class StructurePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $pomm;
    private $decorated;

    public function __construct(
        Pomm $pomm,
        PropertyMetadataFactoryInterface $decorated
    ) {
        $this->pomm = $pomm;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resource_class, string $property, array $options = []): PropertyMetadata
    {
        $parent_prop_metadata = null;
        if ($this->decorated) {
            try {
                $parent_prop_metadata = $this->decorated->create($resource_class, $property, $options);
            } catch (PropertyNotFoundException $exception) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resource_class);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parent_prop_metadata, $resource_class, $property);
        }

        if ($reflectionClass->hasProperty($property)) {
            try {
                $repository = $this->pomm
                    ->getDefaultSession()
                    ->getRepository($resource_class);
            } catch (\Exception $e) {
                throw new \Exception(
                    'Failed to create property metadata.'.PHP_EOL
                    .'Repository for "%s" entity wasn\'t found or an error occured during "%s" property metadata creation process.'.PHP_EOL
                    .'Previous message:'.PHP_EOL. '%s',
                    $resource_class,
                    $property,
                    $e->getMessage()
                );
            }

            $values = [];
            if (true == in_array($property, $repository->getStructure()->getPrimaryKey())) {
                $values['identifier'] = true;
            }

            if (true == array_key_exists($property, $repository->getStructure()->getNotNull())) {
                $values['required'] = true;
            }

            return $this->createMetadata($values, $parent_prop_metadata);
        }

        return $this->handleNotFound($parent_prop_metadata, $resource_class, $property);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param PropertyMetadata|null $parent_prop_metadata
     * @param string                $resource_class
     * @param string                $property
     *
     * @throws PropertyNotFoundException
     *
     * @return PropertyMetadata
     */
    private function handleNotFound(PropertyMetadata $parent_prop_metadata = null, string $resource_class, string $property): PropertyMetadata
    {
        if (null !== $parent_prop_metadata) {
            return $parent_prop_metadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of class "%s" not found.', $property, $resource_class));
    }

    private function createMetadata(array $values, PropertyMetadata $parent_prop_metadata = null): PropertyMetadata
    {
        if (null === $parent_prop_metadata) {
            $prop_metadata = new PropertyMetadata();

            if (null != ($values['identifier'] ?? null)) {
                $prop_metadata = $prop_metadata->withIdentifier($values['identifier']);
            }
            if (null != ($values['required'] ?? null)) {
                $prop_metadata = $prop_metadata->withRequired($values['required']);
            }

            return $prop_metadata;
        }

        $prop_metadata = $parent_prop_metadata;
        foreach ([['get', 'description'], ['is', 'readable'], ['is', 'writable'], ['is', 'readableLink'], ['is', 'writableLink'], ['is', 'required'], ['get', 'iri'], ['is', 'identifier'], ['get', 'attributes']] as $property) {
            if (null !== $value = ($values[$property[1]] ?? null)) {
                $prop_metadata = $this->createWith($prop_metadata, $property, $value);
            }
        }

        return $prop_metadata;
    }

    private function createWith(PropertyMetadata $prop_metadata, array $property, $value): PropertyMetadata
    {
        $getter = $property[0].ucfirst($property[1]);
        if (null !== $prop_metadata->$getter()) {
            return $prop_metadata;
        }

        $wither = 'with'.ucfirst($property[1]);

        return $prop_metadata->$wither($value);
    }
}
