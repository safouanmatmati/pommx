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

namespace Pommx\DependencyInjection;

use Symfony\Component\Config\Definition\NodeInterface;

abstract class AbstractConfiguration
{
    /**
     * Replace directory seperator by platform directory separator
     * @return \closure [description]
     */
    protected function getReplaceDirectorySeparatorClosure(): \closure
    {
        return (function($value) {
            return str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $value);
        });
    }

    /**
     * Return node depending on path.
     *
     * @param  string        $path [description]
     * @param  NodeInterface $node [description]
     * @return null|NodeInterface              [description]
     */
    protected function findNode(string $path, NodeInterface $node): ?NodeInterface
    {
        $paths        = explode('.', $path);
        $current_name = array_shift($paths);
        $current_node = $node;

        do {
            if (false == method_exists($node, 'getChildren')) {
                return null;
            }

            $found = false;
            foreach ($current_node->getChildren() as $name => $child) {
                if ($name == $current_name) {
                    $found        = true;
                    $current_node = $child;
                    $current_name = array_shift($paths);

                    if (true == is_null($current_name)) {
                        return $current_node;
                    }

                    break;
                }
            }
        } while (true == $found);
    }
}
