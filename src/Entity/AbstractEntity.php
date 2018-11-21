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

namespace Pommx\Entity;

use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Model\FlexibleEntity\StatefulEntityTrait;

use Pommx\Relation\RelationsManager;
use Pommx\Relation\RelationTrait;

use Pommx\MapProperties\MapPropertiesManager;
use Pommx\MapProperties\MapPropertiesTrait;

use Pommx\Fetch\FetcherManager;

use Pommx\EntityManager\Annotation\CascadePersist;

use Pommx\Repository\AbstractRepository;

use Pommx\Entity\Traits\MapPropertiesTrait as CustomMapPropertiesTrait;
use Pommx\Entity\Traits\RelationTrait as CustomRelationTrait;

use Pommx\Tools\Exception\ExceptionManagerInterface;
use Pommx\Tools\Exception\ExceptionManagerAwareTrait;
use Pommx\Tools\Exception\ExceptionManagerAwareInterface;
use Pommx\Tools\InheritedReflectionClass;
use Pommx\Tools\UnawareVisibilityTrait;
use Pommx\Tools\DebugTrait;

/**
 * @CascadePersist
 */
abstract class AbstractEntity implements FlexibleEntityInterface, ExceptionManagerAwareInterface
{
    use ExceptionManagerAwareTrait;
    use CustomMapPropertiesTrait;
    use CustomRelationTrait;
    use DebugTrait;
    use StatefulEntityTrait;
    use UnawareVisibilityTrait;

    const STATUS_TO_DELETE = 4;
    const STATUS_DELETED   = 8;
    const STATUS_PROXY     = 16;

    const ALL_STATUS = [
        self::STATUS_NONE,
        self::STATUS_EXIST,
        self::STATUS_MODIFIED,
        self::STATUS_TO_DELETE,
        self::STATUS_DELETED,
        self::STATUS_PROXY
    ];

    /**
     * [protected description]
     *
     * @var array
     */
    private $original_values = [];

    /**
     *
     * @var bool
     */
    private $initialized = false;

    /**
     *
     * @var array sandbox
     */
    private $sandbox = [];

    /**
     * [private description]
     *
     * @var FetcherManager
     */
    protected $fetcher_manager;

    /**
     * __construct
     *
     * Instantiate the entity.
     *
     * @param Exception|null $exception_manager
     */
    public function __construct()
    {
        $this->defineHash();
    }

    /**
     * fields
     *
     * Return the fields array. If a given field does not exist, an exception
     * is thrown.
     *
     * @throws \InvalidArgumentException
     * @see    FlexibleEntityInterface
     */
    public function fields(array $fields = null)
    {
        if ($fields === null) {
            return $this->original_values;
        }

        $output = [];

        foreach ($fields as $name) {
            if (isset($this->original_values[$name]) || array_key_exists($name, $this->original_values)) {
                $output[$name] = $this->original_values[$name];
                continue;
            }

            $this->throw(
                __LINE__,
                sprintf(
                    "No such field '%s'. Existing fields are {%s}",
                    $name,
                    join(', ', array_keys($this->original_values))
                ),
                \InvalidArgumentException::class,
                self::class
            );
        }

        return $output;
    }

    /**
     * Pre initialize.
     *
     * Defines exception manager
     *
     * @param  ExceptionManagerInterface $exception_manager [description]
     * @return self                                    [description]
     */
    protected function preInitialize(ExceptionManagerInterface $exception_manager)
    {
        if (false == $this->isInitialized()) {
            $this->setExceptionManager($exception_manager);
        }

        return $this;
    }

    /**
     * Define values, attachs entities services, initialize traits ...
     *
     * @return self
     */
    protected function initialize(
        FetcherManager $fetcher_manager,
        MapPropertiesManager $map_prop_manager,
        RelationsManager $rel_entities_manager,
        array $structure,
        array $values
    ): self {
        if (false == $this->isInitialized()) {
            $this->initialized = true;

            // The methods calls order is important.

            // Add entity structure with null values
            // merge them with entity default values (defined during instanciation)
            // merge all with current argument $values
            $values = $values
            +$this->fields()
            +array_fill_keys($structure, null);

            // Defines $original_values/structure values
            $this->hydrate(array_intersect_key($values, $structure = array_flip($structure)));

            // Defines properties values
            $this->setProperties($values);

            // Define fetcher manager & create proxies
            $this->fetcher_manager = $fetcher_manager;

            // Initialize from managers
            $map_prop_manager->initialize($this); // map properties
            $rel_entities_manager->initialize($this); // defines relations

            // Defines final structure values from mapped ones
            $this->hydrate(
                array_intersect_key(
                    $this->extract(array_flip($structure)),
                    $structure
                )
            );

            // Creates proxies
            $this->fetcher_manager->defineProxies($this);
        } else {
            $this->onUpdate();
        }

        return $this;
    }

    /**
     * Called instead of {@see AbstractEntity::initialize()}, when entity is already initialized.
     *
     * @return self
     */
    protected function onUpdate(): self
    {
        // Synchronizes relations
        $this->relationSyncAll();

        // Maps properties
        $this->mapPropSyncAll();

        return $this;
    }

    /**
     * Indicates if entity is initialized.
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * {@inheritdoc}
     * Updates $original_values data & corresponding properties.
     *
     * Do not call it, excepts for Pomm upgrade.
     * Used during initialization.
     *
     * @access private
     * @param  array $values
     * @return self
     */
    final public function hydrate(array $values)
    {
        $this->original_values = array_merge($this->original_values, $values);

        $this->setProperties($values);

        return $this;
    }

    /**
     * Set properties values.
     * Take care of current values before changing them.
     *
     * @param  array $values
     * @return self
     */
    private function setProperties(array $values): self
    {
        foreach ($values as $property => $value) {
            if (false == property_exists($this, $property)) {
                $this->setUnawareVisibility($this, $property, $value);
                continue;
            }

            $current_val = $this->getUnawareVisibility($this, $property);

            if (true == is_array($value)) {
                if (true == is_array($current_val)) {
                    $this->setUnawareVisibility($this, $property, $value+$current_val);
                    continue;
                }
            }

            if (true == is_null($current_val)) {
                $this->setUnawareVisibility($this, $property, $value);
            }
        }

        return $this;
    }

    /**
     * Returns property value.
     *
     * Default get call, except :
     * - if "@Fetch" annotation exists,  call fetcher manager to fetch it before.
     *
     * @param  string $property [description]
     * @return mixed           [description]
     */
    protected function get(string $property)
    {
        $this->fetcher_manager->fetchProperty($this, $property);

        return $this->getUnawareVisibility($this, $property);
    }

    /**
     * Set property value.
     *
     * Default set call, except :
     * - if "@Relation" annotation exists, call relation manager to set it
     * - if "@MapValue" annotation exists,  call map property manager to set properties related to it
     *
     * @param  string $property [description]
     * @param  mixed  $value    [description]
     * @return self             [description]
     */
    protected function set(string $property, $value): self
    {
        $this->relationAutoSet($property, $value);
        $this->mapPropSet($property, $value);

        return $this;
    }

    /**
     * Add value to array property.
     * Use value as index to add it, except :
     * - if "@Relation" annotation exists, call relation manager to add it.
     *
     * @param  string $property [description]
     * @param  mixed  $value    [description]
     * @return self             [description]
     */
    protected function addTo(string $property, $value): self
    {
        if (true == $this->relationAutoAdd($property, $value)) {
            return $this;
        }

        $values   = $this->getUnawareVisibility($this, $property);
        $values[] = $value;
        $this->setUnawareVisibility($this, $property, $values);

        return $this;
    }

    /**
     * Removes value from array property.
     * Use value as index to "unset" it, except :
     * - if "@Relation" annotation exists, call relation manager to remove it.
     *
     * @param  string $property [description]
     * @param  mixed  $value    [description]
     * @return self             [description]
     */
    protected function removeFrom(string $property, $value): self
    {
        if (true == $this->relationAutoRemove($property, $value)) {
            return $this;
        }

        $values = $this->getUnawareVisibility($this, $property);

        if (false == is_null($values) && false == is_array($values)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to remove value from "%s::$%s" property.'.PHP_EOL
                    .'This property is not an array.'.PHP_EOL
                    .'"array" expected, "%s" type found',
                    static::class,
                    $property,
                    gettype($values)
                )
            );
        } elseif (true == is_null($values)) {
            return $this;
        }

        if (true == is_bool($value) || false == is_scalar($value)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to remove value from "%s::$%s" property.'.PHP_EOL
                    .'Invalid "$value" argument.'.PHP_EOL
                    .'"scalar" (but not a boolean) expected, "%s" type found',
                    static::class,
                    $property,
                    gettype($value)
                )
            );
        }

        unset($values[$value]);

        $this->setUnawareVisibility($this, $property, $values);

        return $this;
    }

    /**
     * Check value in array property.
     * Use value as index to check it, except :
     * -  if "@Relation" annotation exists, call relation manager to check it.
     *
     * @param  string $property [description]
     * @param  mixed  $value    [description]
     * @return bool             [description]
     */
    protected function containedIn(string $property, $value): bool
    {
        if (true == $this->relationAutoHas($property, $value)) {
            return $this;
        }

        if (true == is_bool($value) || false == is_scalar($value)) {
            $this->exception_manager->throw(
                self::class,
                __LINE__,
                sprintf(
                    'Failed to check if value is in "%s::$%s" property.'.PHP_EOL
                    .'Invalid "$value" argument.'.PHP_EOL
                    .'"scalar" (but not a boolean) expected, "%s" type found',
                    static::class,
                    $property,
                    gettype($value)
                )
            );
        }

        $values = $this->getUnawareVisibility($this, $property);
        return is_array($values) && isset($values[$value]);
    }

    /**
     * Returns properties name list that shouldn't be part of datas list.
     *
     * @return array
     */
    protected static function excludeFromData(): array
    {
        return [
            'fetcher_manager',
            'sandbox',
            'exception_manager',
            'initialized',
            'unaware_functions'
        ];
    }

    /**
     * Returns data filtered by "$names".
     *
     * @param  array      $data  [description]
     * @param  array|null $names [description]
     * @return array         [description]
     */
    private function filter(array $data, ?array $names): array
    {
        if (true == empty($names)) {
            return $data;
        }

        $filtered = array_intersect_key($data, ($names = array_flip($names)));

        // Checks missing keys
        if (false == empty($diff = array_diff_key($names, $filtered))) {
            $this->throw(
                __LINE__,
                sprintf(
                    'Filter failed. No such data {"%s"}.'.PHP_EOL
                    .'Existing data are {"%s"}',
                    join('", "', array_keys($diff)),
                    join('", "', array_keys($data))
                )
            );
        }

        return array_replace($names, $filtered);
    }

    /**
     * Returns datas.
     *
     * @param  string[]|null $names
     * @return array
     */
    public function extract(array $names = null): array
    {
        // Synchronizes mapped properties
        $this->mapPropSyncAll($names);

        $datas = $this->fields();

        // Excludes some data
        $excluded = self::excludeFromData();
        $datas    = array_diff_key($datas, array_flip($excluded));

        $ref      = new \ReflectionObject($this);

        // Merge "$original_values" (as $this->fields()) & properties values
        foreach ($ref->getProperties() as $property) {
            // Excludes some data
            if (true == in_array($property->getName(), $excluded)) {
                continue;
            }

            $property->setAccessible(true);
            $datas[$property->getName()] = $property->getValue($this);
        }

        // Filter
        $datas = $this->filter($datas, $names);

        ksort($datas);

        return $datas;
    }

    /**
     * Set status.
     *
     * Note: If "STATUS_NONE" flag is set to true, it will replace all previous flags.
     * So, in $flags argument and with other flags, its position will be determinant.
     *
     * @param  bool[] $flags
     * @return self
     */
    public function setStatus(array $flags): self
    {
        foreach ($flags as $status => $enabled) {
            if (false == in_array($status, self::ALL_STATUS)
                || false == is_bool($enabled)
            ) {
                $this->throw(
                    __LINE__,
                    sprintf(
                        'Failed to set entity status.'.PHP_EOL
                        .'Status with key as "%s" doesn\'t exists or value is type of "%s", boolean expected.'.PHP_EOL
                        .'Set one of following value {"%s"} to "true" or "false".'.PHP_EOL
                        .'Example : $entity->setStatus([AbstractEntity::STATUS_MODIFIED => true, AbstractEntity::STATUS_TO_DELETE, false])',
                        $status,
                        gettype($enabled),
                        join('", "', self::ALL_STATUS)
                    )
                );
            }

            if (self::STATUS_NONE === $status && true == $enabled) {
                $this->status($status);
                continue;
            }

            if (true == $enabled) {
                $this->status($this->status() | $status);
                continue;
            }

            if (true == $this->isStatus($status)) {
                $this->status($this->status() ^ $status);
            }
        }

        return $this;
    }

    /**
     * Tests status.
     *
     * @param  int $flags
     * @return bool
     */
    public function isStatus(int ...$flags): bool
    {
        $result = true;
        foreach ($flags as $flag) {
            if (self::STATUS_NONE === $flag) {
                $result = $result && ((bool) ($this->status() === $flag));
                continue;
            }
            $result = $result && ((bool) ($this->status() & $flag));
        }

        return $result;
    }

    /**
     * TODO make it private and use a Sandbox object,
     * Sandbox object will allow data acces only for the owner (example $entity->getFromSandBox('key', $this)) 
     *
     * Returns a sandox used to store sensible extra datas.
     *
     * @param  string|null $key
     * @return array
     */
    public function &getSandbox(string $key = null): array
    {
        if (true == is_null($key)) {
            return $this->sandbox;
        }

        if (false == array_key_exists($key, $this->sandbox)) {
            $this->sandbox[$key] = [];
        }

        return $this->sandbox[$key];
    }

    /**
     * Defines unique object hash.
     *
     * @return self
     */
    private function defineHash(): self
    {
        $this->getSandbox(self::class)['hash'] = md5(spl_object_hash($this).rand());
        return $this;
    }

    /**
     * Returns unique object hash.
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->getSandbox(self::class)['hash'];
    }

    /**
     * Dump current object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->debugInfoMasked(
            [
                'exception_manager',
                'sandbox',
                'fetcher_manager',
                'unaware_functions'
            ]
        );
    }
}
