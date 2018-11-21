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

namespace Pommx\Bridge\ApiPlatform\Serializer;

use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

use PommProject\Foundation\Pomm;

use Pommx\Entity\AbstractEntity;

class EntityNormalizer implements
    SerializerAwareInterface,
    ContextAwareDenormalizerInterface,
    CacheableSupportsMethodInterface
{
    use SerializerAwareTrait;

    /**
     *
     * @var Pomm
     */
    private $pomm;

    /**
     * [__construct description]
     *
     * @param Pomm $pomm
     */
    public function __construct(Pomm $pomm)
    {
        $this->pomm = $pomm;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        try {
            $repository = $this->pomm
                ->getDefaultSession()
                ->getRepository($class);

            $entity = $repository->createEntity();
        } catch (\Exception $e) {
            throw new \Exception(
                'Failed to instanciate entity.'.PHP_EOL
                .'Repository of "%s" entity wasn\'t found or an error occured during entity creation process.'.PHP_EOL
                .'Previous message:'.PHP_EOL. '%s',
                $class,
                $e->getMessage()
            );
        }

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $entity;

        return $this->serializer->denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return (true == is_subclass_of($type, AbstractEntity::class))
        && 'post' === ($context['collection_operation_name'] ?? null)
        && false == isset($context[AbstractNormalizer::OBJECT_TO_POPULATE]);
    }
}
