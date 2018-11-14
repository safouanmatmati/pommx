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

namespace PommX\Serializer\Mapping\Loader;

use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

use PommProject\Foundation\Pomm;

use PommX\Entity\AbstractEntity;

/**
 * StructureLoader.
 *
 * Add AbstractEntity's structure fields as part of class attributes.
 */
class StructureLoader implements LoaderInterface
{
    /**
     *
     * @var Pomm
     */
    private $pomm;

    /**
     *
     * @param Pomm $pomm
     */
    public function __construct(Pomm $pomm)
    {
        $this->pomm = $pomm;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $class_metadata)
    {
        if (false == $this->supports($class = $class_metadata->getName())) {
            return false;
        }

        $repository = $this->pomm
            ->getDefaultSession()
            ->getRepository($class);

        $attributes_metadata = $class_metadata->getAttributesMetadata();

        foreach ($repository->getStructure()->getFieldNames() as $attribute) {
            if (false == isset($attributes_metadata[$attribute])) {
                $class_metadata->addAttributeMetadata(new AttributeMetadata($attribute));
            }
        }

        return true;
    }

    /**
     *
     * @param  string $class
     * @return bool
     */
    private function supports(string $class): bool
    {
        return true == is_subclass_of($class, AbstractEntity::class);
    }
}
