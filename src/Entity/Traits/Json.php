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

/**
 * This file is part of the Weasyo package.
 *
 * Safouan Matmati <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Sep 28, 2017
 * PHP Version 7.1
 *
 * @package    Undefined
 * @subpackage Undefined
 * @author     Safouan Matmati <safouan.matmati@gmail.com>
 * @license    https://undefined undefined
 * @version    GIT: <git_undefined>
 * @see        Undefined
 * @copyright  2017-2018 Weasyo - all rights reserved
 */
namespace Pommx\Entity\Traits;

use Pommx\Tools\Exception\ExceptionManager;

trait Json
{

    protected function &traitJsonGetSandbox():array
    {
        self::traitJsonThrowException(
            __LINE__,
            sprintf('"%s::traitJsonGetSandbox" method doesn\'t exists, it has to be defined .', static::class)
        );
    }

    /**
     * Serialize $data to json format
     *
     * @param  array $data
     * @return string json encoded
     */
    public static function traitJsonArrayToJson(array $data): string
    {
        return json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_NUMERIC_CHECK);
    }

    /**
     * Serialize $dataObject to json format
     *
     * @param  object $dataObject
     * @return string json encoded
     */
    public static function traitJsonObjectToJson($dataObject): string
    {
        return json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_NUMERIC_CHECK);
    }

    /**
     * Deserialize $dataJson to associative array format
     *
     * @param  string $dataJson
     * @return array
     */
    public static function traitJsonJsonToArray(string $dataJson)
    {
        return json_decode($dataJson, true);
    }

    /**
     * Check $dataJson integrity
     *
     * @param  string      $dataJson
     * @param  string|null &$msg
     * @return bool
     */
    public static function traitJsonIsJsonWellFormatted(
        string $dataJson,
        string &$msg = null
    ): bool {

        $json = json_decode($dataJson);
        $msg = json_last_error_msg();

        return json_last_error() === JSON_ERROR_NONE ? true : false;
    }

    /**
     * Return indented json from array
     *
     * @param  array  $array
     * @param  string $indentation
     * @param  string $backspace
     * @param  string $prevIndentation
     * @return string
     */
    public static function traitJsonIndentJsonFromArray(
        array $array,
        string $indentation = '    ',
        string $backspace = "\n",
        string $prevIndentation = ''
    ): string {
        $keys = array_keys($array);
        $isAssociative = !empty(array_diff_assoc($keys, array_keys($keys)));

        $json = $isAssociative ? '{' : '[';

        $length = count($array);
        $index = 0;
        $json .= $length == 0 ? '' : $backspace;

        foreach ($array as $key => $value) {
            $index++;
            $json .= $prevIndentation
             . $indentation
             . ($isAssociative ? '"'.$key .'": ' : '');

            if (true == is_array($value)) {
                $json .= self::traitJsonIndentJsonFromArray(
                    $value,
                    $indentation,
                    $backspace,
                    $prevIndentation . $indentation
                );
            } elseif (true == is_bool($value)) {
                $json .= $value ? 'true' : 'false';
            } elseif (true == is_null($value)) {
                $json .= 'null';
            } else {
                $json .= '"' . $value. '"';
            }

            $json .= $index < $length ? ",$backspace" : '';
        }

        $json .= ($length == 0 ? '' : $backspace . $prevIndentation)
         . ($isAssociative ? '}' : ']');

        return $json;
    }

    /**
     * Removes whitespace (as indentation etc...) from json string
     *
     * @param  string $json
     * @return string
     */
    public static function traitJsonRemoveWhitespacesFromJson(string $json): string
    {
        return self::traitJsonArrayToJson(self::traitJsonJsonToArray($json));
    }

    /**
     * Set $data
     *
     * @param  array|null $data
     * @return self
     */
    public function traitJsonSetData($data = null, string $format = null): self
    {
        $this->traitJsonCheckIsInitialized(__LINE__);

        if (false == is_array($data) && false == self::traitJsonIsJsonWellFormatted($data)) {
            self::traitJsonThrowException(
                __LINE__,
                sprintf(
                    'Failed to set data.'.PHP_EOL
                    .'"%s" type found, array or valid json expected.',
                    gettype($data)
                )
            );
        }

        if (false == is_null($format)) {
            switch ($format) {
            case 'array':
                $data = self::traitJsonJsonToArray($data);
                break;
            case 'json':
                $data = self::traitJsonArrayToJson($data);
                break;

            default:
                self::traitJsonThrowException(
                    __LINE__,
                    sprintf(
                        'Invalid "format" parameter.'.PHP_EOL
                        .'"%s" value found, "array" or "json" expected.',
                        $format
                    ),
                    \InvalidArgumentException::class
                );
                break;
            }
        }

        $this->{$this->traitJsonGetJsonPropertyName()} = $data;
        return $this;
    }

    /**
     * Returns $data
     *
     * @return array|null
     */
    public function traitJsonGetData(): ?array
    {
        $this->traitJsonCheckIsInitialized(__LINE__);

        return $this->{$this->traitJsonGetJsonPropertyName()};
    }

    /**
     * Returns element from $data with correponding key ($key)
     *
     * @param  string $key
     * @return mixed
     */
    public function traitJsonGetFromData($key)
    {
        $this->traitJsonCheckIsInitialized(__LINE__);

        $data = $this->getData();

        if (null !== $data && true == array_key_exists($key, $data)) {
            return $data[$key];
        }

        return null;
    }

    /**
     * [addData description]
     *
     * @param mixed       $value
     * @param string|null $key
     * @param bool|null   $replace
     */
    public function traitJsonAddData($value, string $key = null, bool $replace = null)
    {
        $this->traitJsonCheckIsInitialized(__LINE__);

        if (null === $this->{$this->traitJsonGetJsonPropertyName()}) {
            $this->{$this->traitJsonGetJsonPropertyName()} = [];
        }

        if (null !== $key && true == array_key_exists($key, $this->{$this->traitJsonGetJsonPropertyName()}) && true !== $replace) {
            self::traitJsonThrowException(
                __LINE__,
                sprintf(
                    'Data failed to be added, array key "%s" already exists.'.PHP_EOL
                      .'Use "%s" argument to force replacement or use a new key.',
                    $key,
                    '$replace'
                )
            );
        }

        if (null !== $key) {
              $this->{$this->traitJsonGetJsonPropertyName()}[$key] = $value;
        } else {
            $this->{$this->traitJsonGetJsonPropertyName()}[] = $value;
        }
    }

    public function traitJsonGetDataAsJson(): ?string
    {
        return self::traitJsonArrayToJson($this->traitJsonGetData());
    }

    final private function traitJsonGetJsonPropertyName(): string
    {
        $this->traitJsonCheckIsInitialized(__LINE__);

        return $this->traitJsonGetSandbox()['config']['property'];
    }

    final private function traitJsonCheckIsInitialized(int $line)
    {
        if (false == $this->traitJsonIsInitialized()) {
            self::traitJsonThrowException(
                $line,
                sprintf(
                    '"%s" trait isn\'t initialized. Use "%s::%s" to initialize it.',
                    __TRAIT__,
                    static::class,
                    'traitJsonInitialize'
                )
            );
        }
    }

    /**
     * [initialize description]
     */
    final private function traitJsonInitialize(string $property)
    {
        if (true == $this->traitJsonIsInitialized()) {
            return;
        }

        $this->traitJsonInitConfig($property);

        $this->traitJsonGetSandbox()['initialized'] = true;
    }

    final private function traitJsonInitConfig(string $property)
    {
        if (false == property_exists($this, $property)) {
            self::traitJsonThrowException(
                __LINE__,
                sprintf('"%s::$%s" property doesn\'t exists.', static::class, $config['property'])
            );
        }

        $config = ['property' => $property];

        $this->traitJsonGetSandbox()['config'] = $config;
    }

    /**
     * [isInitialized description]
     *
     * @return bool
     */
    final public function traitJsonIsInitialized(): bool
    {
        return isset($this->traitJsonGetSandbox()['initialized']);
    }

    /**
     * [mapPropThrowException description]
     *
     * @param string      $class
     * @param string      $message
     * @param string|null $class_exception
     */
    private static function traitJsonThrowException(int $line, string $message, string $class_exception = null)
    {
        ExceptionManager::throw(
            __TRAIT__,
            $line,
            sprintf(
                '%s.'.PHP_EOL.'See "%s" class',
                $message,
                static::class
            ),
            $class_exception
        );
    }
}
