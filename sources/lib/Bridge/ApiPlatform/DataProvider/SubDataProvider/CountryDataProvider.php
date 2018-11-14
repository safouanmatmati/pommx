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

namespace PommX\Bridge\ApiPlatform\DataProvider\SubDataProvider;

use PommX\Bridge\ApiPlatform\DataProvider\SubDataProvider\AbstractDataProvider;
use Schema\Application\Repository\CountryRepository;
use PommX\Repository\QueryBuilder;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class CountryDataProvider extends AbstractDataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        if (false == array_key_exists('id', $identifiers)) {
            throw new \InvalidArgumentException('identifiers required.');
        }

        $id = $identifiers['languages'] ?? $identifiers['id'];

    }

}
