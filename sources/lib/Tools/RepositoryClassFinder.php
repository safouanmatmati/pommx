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

namespace PommX\Tools;

use PommX\Tools\Exception\ExceptionManagerInterface;

class RepositoryClassFinder
{
    /**
     *
     * @var string[]
     */
    private $repositories_classes = [];

    /**
     * [private description]
     *
     * @var array
     */
    private $patterns;

    /**
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    public function __construct(ExceptionManagerInterface $exception_manager, array $patterns = null)
    {
        $this->exception_manager = $exception_manager;

        if (false == is_null($patterns)) {
            $this->addPatterns($patterns);
        }
    }

    /**
     * Add pattern.
     *
     * @param  string $entity_class_pattern
     * @param  string $repo_class_pattern
     * @return self
     */
    public function addPattern(string $entity_class_pattern, string $repo_class_pattern): self
    {
        // Remove all "\" at the end
        $repo_class_pattern = rtrim($repo_class_pattern, '\\');
        $entity_class_pattern = rtrim($entity_class_pattern, '\\');

        if (true == isset($this->patterns[$entity_class_pattern])) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to add pattern.'.PHP_EOL
                    .'"%s" as entity pattern already exists.',
                    $entity_class_pattern
                )
            );
        }

        $patterns = [
            'repository' => ['pattern' => $repo_class_pattern, 'needles' => []],
            'entity'     => ['pattern' => $entity_class_pattern, 'needles' => []],
        ];
        foreach ($patterns as $type => $data) {
            if (false === preg_match_all('/(?<needles>{[$][^}]+})/', $data['pattern'], $res)) {
                $this->exception_manager->throw(
                    self::class,
                    __LINE__,
                    sprintf(
                        'Failed to add pattern.'.PHP_EOL
                        .'%s patterns "%s" isn\'t valid.',
                        $type,
                        $data['pattern']
                    )
                );
            }

            $patterns[$type]['needles'] = $res['needles'];
        }

        $diff = array_merge(
            array_diff($patterns['entity']['needles'], $patterns['repository']['needles']),
            array_diff($patterns['repository']['needles'], $patterns['entity']['needles'])
        );

        if (false == empty($diff)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to add pattern.'.PHP_EOL
                    .'Patterns aren\'t valid.'.PHP_EOL
                    .'"%s" needles have be used by repository pattern and entity pattern.'.PHP_EOL
                    .'Modify "%s" entity pattern or "%s" repository pattern.',
                    join('", "', $diff),
                    $entity_class_pattern,
                    $repo_class_pattern
                )
            );
        }

        $this->patterns[$entity_class_pattern] = [
            'repo_class_pattern' => $repo_class_pattern,
            'repo_needles' => $patterns['repository']['needles'],
            'entity_needles' => $patterns['entity']['needles'],
            'parts' => []
        ];

        $cleaned_entity = preg_replace('/(?<needles>{[$][^}]+})/', '|', $entity_class_pattern);

        if (false !== preg_match_all('/(^[|]|)(?<parts>[^|]+)([|]|$)/', $cleaned_entity, $res)) {
            $this->patterns[$entity_class_pattern]['parts'] = $res['parts'];
        }

        return $this;
    }

    /**
     * Add list of patterns.
     *
     * @param  array $patterns
     * @return self
     */
    public function addPatterns(array $patterns): self
    {
        foreach ($patterns as $entity_class_pattern => $repo_class_pattern) {
            $this->addPattern($entity_class_pattern, $repo_class_pattern);
        }

        return $this;
    }

    /**
     * Returns repository class path based on $repo_class_pattern
     *
     * If $entity_class_pattern is defined, its needles, as {$my_needle},
     * are replaced by their corresponding value based on $class parameter.
     * Each value is then injected on $repo_class_pattern to generated final repository class path.
     *
     * Ex:
     * $entity_class_pattern = App\{$namespace}\{$schema}Schema\{$class}
     * $repo_class_pattern = App\{$schema}\New{$namespace}\{$class}Repository
     * $class = App\MyNamespace\MyPublicSchema\Test
     * return  App\MyPublic\NewMyNamespace\TestRepository
     *
     * @param  string $class
     * @return string
     */
    public function get(string $class): string
    {
        if (true == isset($this->repositories_classes[$class])) {
            return $this->repositories_classes[$class];
        }

        do {
            $pattern = current($this->patterns);

            $index = -1;
            $founded = $correspondences = [];

            $string = $class;

            foreach ($pattern['parts'] as $part) {
                if (false === ($pos = strpos($string, $part))) {
                    continue 2;
                }

                if ('' != ($found = substr($string, 0, $pos))) {
                    $founded[++$index] = $found;
                }

                $string = substr_replace($string, '', 0, strlen($part)+strlen($found));
            }

            if ('' != $string) {
                if (count($pattern['entity_needles']) == count($founded)) {
                    $founded[$index] .= $string;
                } else {
                    $founded[++$index] = $string;
                }
            }

            foreach ($founded as $key => $value) {
                $correspondences[$pattern['entity_needles'][$key]] = $value;
            }

            $repository = str_replace(
                array_keys($correspondences),
                $correspondences,
                $pattern['repo_class_pattern']
            );
        } while (next($this->patterns) && false == isset($repository));

        reset($this->patterns);

        if (false == isset($repository)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to find repository.'.PHP_EOL
                    .'"%s" entity class doesn\'t match any patterns :'.PHP_EOL
                    .'"%s"',
                    $class,
                    join('",'.PHP_EOL.'"', array_keys($this->patterns))
                )
            );
        }

        $this->repositories_classes[$class] = $repository;

        return $this->repositories_classes[$class];
    }

    public function hasPattern(string $class): bool
    {
        return array_key_exists($class, $this->patterns);
    }
}
