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

namespace PommX\PropertyInfo\Extractor;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Type;

use PommProject\Foundation\Pomm;
use PommProject\SymfonyBridge\PropertyInfo\Extractor\TypeExtractor as PommTypeExtractor;
use PommProject\ModelManager\Exception\ModelException;

use PommX\PropertyInfo\Extractor\AbstractExtractor;

/**
 * TypeExtractor.
 *
 * Define type of properties.
 * From php doc first, else from Pomm structure definition.
 */
class TypeExtractor extends AbstractExtractor implements PropertyTypeExtractorInterface
{
    /**
     * [private description]
     *
     * @var PommTypeExtractor
     */
    private $decorated;

    /**
     *
     * @var Pomm
     */
    private $pomm;

    public function __construct(
        Pomm $pomm,
        PommTypeExtractor $extractor
    ) {
        $this->pomm            = $pomm;
        $this->decorated       = $extractor;
        $this->phpDocExtractor = new PhpDocExtractor();
    }

    /**
     * Returns property type from PHP doc class in priority,
     * or from Pomm "getTypes" otherwise.
     *
     * @param  string $class
     * @param  string $property
     * @param  array  $context
     * @return Type[]|null
     */
    public function getTypes($class, $property, array $context = [])
    {
        if (false == $this->supports($class)) {
            return null;
        }

        try {
            $infos = $this->phpDocExtractor->getTypes($class, $property);

            if (true == isset($infos[0])) {
                return $infos;
            }

            $context['model:name'] = $class;
            $type = $this->decorated->getTypes($class, $property, $context);
        } catch (ModelException $e) {
            $type = null;
        }

        // Add nullable definition
        if (false == empty($type)) {
            $structure = $this->pomm->getDefaultSession()
                ->getRepository($class)
                ->getStructure();
            if ((!$structure->isNotNull($property)) != $type[0]->isNullable()) {
                $value = $type[0];
                $type = [new Type(
                    $value->getBuiltinType(),
                    !$structure->isNotNull($property),
                    $value->getClassName(),
                    $value->isCollection(),
                    $value->getCollectionKeyType(),
                    $value->getCollectionValueType()
                )];
            }
        }

        return $type;
    }
}
