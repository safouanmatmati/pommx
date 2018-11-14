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

namespace PommX\Repository;

use PommProject\Foundation\Where;

use PommX\Tools\Exception\ExceptionManagerInterface;
use PommX\Tools\CheckIntegrityTrait;

class Join
{
    use CheckIntegrityTrait;

    private static $instance_number = 0;

    public const TYPE_INNER = 'INNER';
    public const TYPE_CROSS = 'CROSS';
    public const TYPE_LEFT  = 'LEFT OUTER';
    public const TYPE_RIGHT = 'RIGHT OUTER';
    public const TYPE_FULL  = 'LEFT OUTER';

    public const TYPES = [
        'TYPE_INNER' => self::TYPE_INNER,
        'TYPE_CROSS' => self::TYPE_CROSS,
        'TYPE_LEFT'  => self::TYPE_LEFT,
        'TYPE_RIGHT' => self::TYPE_RIGHT,
        'TYPE_FULL'  => self::TYPE_FULL
    ];

    /**
     * [private description]
     *
     * @var ExceptionManagerInterface
     */
    private $exception_manager;

    /**
     * [private description]
     *
     * @var Join[]
     */
    private $joins = [];

    /**
     * [private description]
     *
     * @var string
     */
    private $type;

    /**
     * [private description]
     *
     * @var string
     */
    private $source;
    /**
     * [private description]
     *
     * @var string
     */
    private $source_alias;

    /**
     * [private description]
     *
     * @var string
     */
    private $related;

    /**
     * [private description]
     *
     * @var string
     */
    private $related_alias;

    /**
     * [private description]
     *
     * @var Where|array|string|null
     */
    private $condition;

    /**
     * [private description]
     *
     * @var array
     */
    private $fields;

    /**
     * [__construct description]
     *
     * @param ExceptionManagerInterface $exception_manager [description]
     */
    public function __construct(ExceptionManagerInterface $exception_manager)
    {
        self::$instance_number++;
        $this->exception_manager = $exception_manager;
    }

    public function setType(string $type): self
    {
        if (false == in_array($type, static::TYPES)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to set "JOIN" type.'.PHP_EOL
                    .'"%s" type doesn\'t exists.'.PHP_EOL
                    .'Available types are {"%s"}.',
                    $type,
                    join('", "', static::TYPES)
                )
            );
        }
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setSource(string $source): self
    {
        $this->setSourceAlias($this->formatAlias($source));

        $this->source = $source;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSourceAlias(string $source_alias): self
    {
        $this->source_alias = $source_alias;

        return $this;
    }

    public function getSourceAlias(): string
    {
        return $this->source_alias;
    }

    public function setRelated(string $related): self
    {
        $this->related = $related;

        $this->setRelatedAlias($this->formatAlias($related));

        return $this;
    }

    private function formatAlias(string $related): string
    {
        return sprintf('%s_%d_alias', str_replace('.', '_', $related), self::$instance_number);
    }

    public function getRelated(): string
    {
        return $this->related;
    }

    public function setRelatedAlias(string $related_alias): self
    {
        $this->related_alias = $related_alias;

        return $this;
    }

    public function getRelatedAlias(): string
    {
        return $this->related_alias;
    }

    public function setMappedCondition(array $condition): self
    {
        $message = 'Failed to set "JOIN" mapped condition (eq "ON" clause).';

        try {
            self::checkArrayIntegrity(
                $condition,
                ['string'],
                true,
                $this->exception_manager
            );
        } catch (\Exception $e) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                $message,
                null,
                $e
            );
        }

        foreach (array_keys($condition) as $key) {
            try {
                self::checkIntegrity(
                    'key from $condition',
                    $key,
                    ['string'],
                    $this->exception_manager
                );
            } catch (\Exception $e) {
                $this->exception_manager->throw(
                    self::class,
                    __LINE__,
                    $message,
                    null,
                    $e
                );
            }
        }

        $this->condition = $condition;

        return $this;
    }

    public function setCondition(Where $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    public function getCondition(): string
    {
        if (true == is_string($this->condition)) {
            return $this->condition;
        }

        if (true == is_a($this->condition, Where::class)) {
            return str_replace(
                array_fill(0, count($values = $this->condition->getValues()), '$*'),
                $values,
                $this->condition
            );
        }

        if (true == is_array($this->condition)) {
            $sql = [];

            foreach ($this->condition as $key => $value) {
                $sql[] = sprintf(
                    '%s.%s = %s.%s',
                    $this->getSourceAlias(),
                    $key,
                    $this->getRelatedAlias(),
                    $value
                );
            }

            return sprintf('ON %s', join(' AND ', $sql));
        }
        $this->exception_manager->throw(
            self::class,
            __LINE__,
            sprintf(
                'Failed to return "JOIN" condition.'.PHP_EOL
                .'"%s" type found, ["string", "array", "%s"] expected.',
                gettype($this->condition),
                Where::class
            )
        );
    }

    public function invert()
    {
        $source = $this->getSource();
        $related = $this->getRelated();

        $this->setSource($related);
        $this->setRelated($source);

        if (true == is_array($this->condition)) {
            $this->condition = array_flip($this->condition);
        }
    }

    public function setUsing(array $fields): self
    {
        $this->condition = sprintf('USING (%s)', join(', ', $fields));

        return $this;
    }

    public function setField(string $name, $content, $type = null): self
    {
        $this->fields[$name] = [
            'content' => $content,
            'type' => $type
        ];

        return $this;
    }

    public function getField(string $name): ?array
    {
        return $this->fields[$name] ?? null;
    }

    public function setFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->setField($field['name'], $field['content'], $field['type'] ?? null);
        }
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addJoin(Join $join, string $name = null): self
    {
        $name = $name ?? $join->getRelated();

        if (true == isset($this->joins[$name])) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to add "JOIN".'.PHP_EOL
                    .'A join with "%s" as name already defined.',
                    $name
                )
            );
        }

        $this->joins[$name] = $join;
        $join
            ->setSource($this->getRelated())
            ->setSourceAlias($this->getRelatedAlias());

        return $this;
    }

    public function removeJoin(string $name): self
    {
        unset($this->joins[$name]);

        return $this;
    }

    public function getJoin(string $name): Join
    {
        return $this->joins[$name];
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function __toString(): string
    {
        return $this->parse();
    }

    protected function parse(): string
    {
        $sql = ':type JOIN :related AS :alias :condition :joins';

        $sql = strtr($sql, [':joins' => join(' ', $this->getJoins())]);

        $replacement = [
            ':type'      => $this->getType(),
            ':related'   => $this->getRelated(),
            ':alias'     => $this->getRelatedAlias(),
            ':condition' => $this->getCondition()
        ];

        return strtr($sql, $replacement);
    }
}
