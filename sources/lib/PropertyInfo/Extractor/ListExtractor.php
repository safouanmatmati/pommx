<?php

/*
 * This file is part of the Weasyo package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PommX\PropertyInfo\Extractor;

use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;

use PommX\PropertyInfo\Extractor\AbstractExtractor;
use PommX\Serializer\Mapping\Factory\ClassMetadataFactory;

/**
 * Defines entity properties
 */
class ListExtractor extends AbstractExtractor implements PropertyListExtractorInterface
{
    /**
     *
     * @var ClassMetadataFactory
     */
    private $class_metadata_factory;

    /**
     *
     * @var AttributeMetadataInterface[]
     */
    private $properties_metadata = [];

    /**
     * [private description]
     *
     * @var string[]
     */
    private $properties = [];

    public function __construct(ClassMetadataFactory $class_metadata_factory)
    {
        $this->class_metadata_factory = $class_metadata_factory;
    }

    /**
     *
     * @param  string $class
     * @param  array  $context
     * @return string[]
     */
    public function getProperties($class, array $context = [])
    {
        $key = $class.'.'.join($context['serializer_groups'] ?? []);

        if (true == isset($this->properties[$key])) {
            return $this->properties[$key];
        }

        $properties_metadata = $this->getPropertiesMetadata($class);

        if (true == isset($context['serializer_groups'])) {
            foreach ($properties_metadata as $name => $metadata) {
                if (true == empty(array_intersect($metadata->getGroups(), $context['serializer_groups']))) {
                    unset($properties_metadata[$name]);
                }
            }

            return $this->properties[$key] = array_keys($properties_metadata);
        }

        return $this->properties[$key] = array_keys($properties_metadata);
    }

    /**
     *
     * @param  string $class
     * @return AttributeMetadataInterface[]|null[]
     */
    public function getPropertiesMetadata(string $class): array
    {
        if (true == isset($this->properties_metadata[$class])) {
            return $this->properties_metadata[$class] ;
        }

        if (false == $this->supports($class)
            || false == $this->class_metadata_factory->hasMetadataFor($class)
        ) {
            return $this->properties_metadata[$class] = null;
        }

        return $this->properties_metadata[$class] = $this->class_metadata_factory
            ->getMetadataFor($class)
            ->getAttributesMetadata();
    }
}
