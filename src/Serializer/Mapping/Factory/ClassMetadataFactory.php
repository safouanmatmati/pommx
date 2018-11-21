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

namespace Pommx\Serializer\Mapping\Factory;

use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory as SymfonyClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassResolverTrait;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;

use PommProject\Foundation\Pomm;

use Pommx\Serializer\Mapping\Loader\StructureLoader;

use Pommx\Entity\AbstractEntity;
use Pommx\Tools\Exception\ExceptionManagerInterface;

/**
 * Returns a {@see ClassMetadata}.
 */
class ClassMetadataFactory extends SymfonyClassMetadataFactory
{
    use ClassResolverTrait;

    /**
     *
     * @var ClassMetadataInterface[]
     */
    private $metadata;

    /**
     *
     * @var Pomm
     */
    private $pomm;

    public function __construct(
        LoaderChain $loader,
        StructureLoader $structure_loader,
        Pomm $pomm
    ) {
        $this->pomm = $pomm;

        // Create a new chain loader with structure loader included.
        $loaders   = $loader->getLoaders();
        $loaders[] = $structure_loader;
        $loader    = new LoaderChain($loaders);

        parent::__construct($loader);
    }

    /**
     * Returns class metadata populated with attributes defined as "allowed".
     *
     * "allowed" attributes are :
     * - public entity properties
     * - entity structure fields not mapped to a no-public property
     *
     * @param  mixed $value
     * @return ClassMetadataInterface[]
     */
    public function getMetadataFor($value)
    {
        if (true == isset($this->metadata[$class = $this->getClass($value)])) {
            return $this->metadata[$class];
        }

        $metadata = parent::getMetadataFor($class);

        if (false == $this->supports($class)) {
            return $this->metadata[$class] = $metadata;
        }

        // Add entity structure
        $structure = $this->pomm
            ->getDefaultSession()
            ->getRepository($class)
            ->getStructure()
            ->getFieldNames();

        $structure = array_combine($structure, $structure);

        $ref        = $metadata->getReflectionClass();
        $properties = [];

        // Add all public properties
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[] = $property->getName();
        }

        // Excludes structure fields mapped to a "no public" property
        foreach ($ref->getProperties(~\ReflectionProperty::IS_PUBLIC) as $property) {
            unset($structure[$property->getName()]);
        }

        $attributes = array_unique($properties + $structure);

        $new_metadata        = new ClassMetadata($class);
        $attributes_metadata = $metadata->getAttributesMetadata();

        foreach ($attributes as $attribute) {
            if (true == isset($attributes_metadata[$attribute])) {
                $new_metadata->addAttributeMetadata($attributes_metadata[$attribute]);
            }
        }

        return $this->metadata[$class] = $new_metadata;
    }

    /**
     * [supports description]
     *
     * @param  string $class
     * @return bool
     */
    private function supports(string $class)
    {
        return is_subclass_of($class, AbstractEntity::class);
    }
}
