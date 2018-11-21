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

namespace Pommx\Fetch\Annotation;

/**
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Fetch
{
    /**
     * Indicates to load the property as part of default entity structure results.
     * NOTE: By default, corresponding join statement is injected in the SQL query.
     *
     * @var string
     */
    const MODE_JOIN = 'JOIN';

    /**
     * Indicates to create a proxy if the property value is null.
     * NOTE: A mapping is done between current entity structure and the related one,
     * to create a proxy with the right primary key.
     *
     * @var string
     */
    const MODE_PROXY = 'PROXY';

    /**
     * Indicates to retrieve data of the property only when the entity is accessed.
     *
     * NOTE: All properties marked as "lazy" are load at the same time.
     *
     * @var string
     */
    const MODE_LAZY  = 'LAZY';

    /**
     * Indicates to retrieve the current property only when its accessed.
     *
     * @var string
     */
    const MODE_EXTRA_LAZY  = 'EXTRA_LAZY';

    /**
     *
     * @Enum({"JOIN", "PROXY", "LAZY", "EXTRA_LAZY"})
     * @Required
     */
    public $mode;

    /**
     * Related entity class name.
     *
     * Current entity primary key value is used to map foreign key related entities.
     *
     * @var mixed
     */
    public $from;

    /**
     * Related entity class name.
     *
     * Current entity foreign key value are used to map primary key related entities.
     *
     * @var mixed
     */
    public $to;

    /**
     * Related entity class name depending on a join condition.
     * {"middle_entity_class"="final_entity_class"}
     *
     * Current entity foreign key value are used to map primary key related entities.
     *
     * @var mixed
     */
    public $join;

    /**
     * Defines properties values of the proxy.
     *
     * @var mixed
     */
    public $values;

    /**
     * Foreign key names lists.
     *
     * Filter foreign key to use to create joins.
     * All "usefull" foreign key or used by default.
     *
     * @var mixed
     */
    public $map;

    /**
     * [public description]
     * @var mixed
     */
    public $callback;
}
