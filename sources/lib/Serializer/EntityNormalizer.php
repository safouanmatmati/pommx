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

namespace PommX\Serializer;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use PommProject\Foundation\Pomm;

use PommX\Serializer\Mapping\Factory\ClassMetadataFactory;
use PommX\PropertyInfo\Extractor\TypeExtractor;
use PommX\Entity\AbstractEntity;

class EntityNormalizer extends ObjectNormalizer implements CacheableSupportsMethodInterface
{
    /**
     *
     * @var Pomm
     */
    protected $pomm;

    /**
     *
     * @var ClassMetadataFactory
     */
    protected $metadata_factory;

    /**
     * {@inheritdoc}
     *
     * @param Pomm                                $pomm
     * @param ClassMetadataFactory                $metadata_factory
     * @param PropertyAccessorInterface           $property_accessor
     * @param TypeExtractor                       $type_extractor
     * @param ClassDiscriminatorResolverInterface $discr_resolver
     * @param NameConverterInterface|null         $name_converter
     */
    public function __construct(
        Pomm $pomm,
        ClassMetadataFactory $metadata_factory,
        PropertyAccessorInterface $property_accessor,
        TypeExtractor $type_extractor,
        ClassDiscriminatorResolverInterface $discr_resolver,
        NameConverterInterface $name_converter = null
    ) {
        parent::__construct($metadata_factory, $name_converter, $property_accessor, $type_extractor, $discr_resolver);

        $this->pomm = $pomm;
        $this->metadata_factory = $metadata_factory;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractAttributes($object, $format = null, array $context = [])
    {
        return array_keys(
            $this->classMetadataFactory->getMetadataFor($object)->getAttributesMetadata()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        try {
            $repository = $this->pomm
                ->getDefaultSession()
                ->getRepository($class);

            $entity = $repository->createEntity();
        } catch (\Throwable $e) {
            throw new \LogicException(
                'Failed to denormalize data.'.PHP_EOL
                .'Repository for "%s" entity wasn\'t found or an error occured during denormalization process.'.PHP_EOL
                .'Previous message:'.PHP_EOL. '%s',
                $class,
                $e->getMessage()
            );
        }

        $context[parent::OBJECT_TO_POPULATE] = $entity;

        $denormalized = parent::denormalize($data, $class, $format, $context);

        // Define current values as original values
        $denormalized->hydrate($denormalized->extract());

        return $denormalized;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return ($data instanceof AbstractEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return (true == is_subclass_of($type, AbstractEntity::class));
    }
}
