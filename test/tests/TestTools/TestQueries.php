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

use Pommx\Repository\AbstractRepository;

trait TestQueries
{
    public function toolTestListenedQueries(AbstractRepository $repo, array $expected_queries)
    {
        $queries      = $repo_queries = [];
        $class        = get_class($repo);
        $repo_queries = $repo->testToolGetListenedQueries();

        if (true == empty($expected_queries)) {
            $message = sprintf(
                'Queries executed from "%s" repository are invalid.'.PHP_EOL
                .'No one expected, %d queries found.',
                $class,
                count($repo_queries)
            );

            $this->assertCount(0, $repo_queries, $message);
        }

        foreach ($expected_queries as $order => $expected) {
            if (false == is_null($query = $repo_queries[$order] ?? null)) {
                $queries[] = $query['sql'];

                $message = sprintf(
                    'Query n°%d from "%s" repository is invalid.'.PHP_EOL,
                    $order,
                    $class
                );

                $this->assertEquals($expected['sql'], $query['sql'], $message);

                $message = sprintf(
                    'Query n°%d from "%s" repository hasn\'t expected values.'.PHP_EOL
                    .'Query : '.PHP_EOL.'- "%s"'.PHP_EOL.PHP_EOL
                    .'Expected values :'.PHP_EOL.'"%s"'.PHP_EOL
                    .'Found  :'.PHP_EOL.'"%s"',
                    $order,
                    $class,
                    $expected['sql'],
                    join('", "', $expected['values']),
                    join('", "', $query['values'])
                );

                $this->assertCount(count($expected['values']), $query['values'], $message);
                $this->assertEquals($expected['values'], $query['values'], $message);
            }

            $message = sprintf(
                'Query n°%d from "%s" repository doesn\'t exists as expected.'.PHP_EOL
                .'Expected : '.PHP_EOL.'- "%s"'.PHP_EOL.PHP_EOL
                .'Found :'.PHP_EOL.'- "%s"'.PHP_EOL,
                $order,
                $class,
                $expected['sql'],
                join('"'.PHP_EOL.'- "', $queries)
            );

            $this->assertArrayHasKey($order, $repo_queries, $message);
        }

        $message = sprintf(
            'Number of queries from "%s" repository is not as expected.',
            $class
        );

        $this->assertCount(count($expected_queries), $repo_queries, $message);

        $repo->testToolClearListenedQueries();
    }
}
