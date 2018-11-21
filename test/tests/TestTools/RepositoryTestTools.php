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

namespace App\Tests\TestTools;

use PommProject\ModelManager\Model\Projection;

use Pommx\Repository\AbstractRepository;
use Pommx\Repository\EmptyResultIterator;

trait RepositoryTestTools
{
    private $queries_listened = [];

    /**
     * [private description]
     * @var [type]
     */
    private $test_injected_results = [];

    public function query($sql, array $values = [], Projection $projection = null, $extra_parameters = null)
    {
        $this->queries_listened[] = [
            'sql'              => $sql,
            'values'           => $values,
            'projection'       => $projection,
            'extra_parameters' => $extra_parameters
        ];

        return EmptyResultIterator::getInstance($this->getSession());
    }

    public function testToolGetListenedQueries(): array
    {
        return $this->queries_listened;
    }

    public function testToolClearListenedQueries(): AbstractRepository
    {
        $this->queries_listened = [];

        return $this;
    }

    public function testToolInjectMethodeResults(string $method, $results)
    {
        $this->test_injected_results[$method][] = $results;
    }

    public function testToolGetInjectedMethodeResults(string $method, ... $arguments)
    {
        $this->test_injected_results[$method] = $this->test_injected_results[$method] ?? [];

        $results = array_shift($this->test_injected_results[$method]);

        if (true == is_callable($results)) {
            $results = call_user_func_array($results, $arguments);
        }

        return $results;
    }
}
