<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util\Type;

use function array_key_exists;
use Exception;
use Plugin\ActivityStreamsTwo\Util\Type;
use ReflectionClass;

/**
 * \ActivityPhp\Type\ObjectAbstract is an abstract class for all
 * Activity Streams Core Types.
 *
 * @see https://www.w3.org/TR/activitystreams-core/#model
 */
abstract class AbstractObject
{
    /**
     * Keep all properties values that have been set
     *
     * @var array
     */
    private array $_props = [];

    /**
     * Standard setter method
     * - Perform content validation if a validator exists
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws Exception
     *
     * @return $this
     */
    public function set(string $name, mixed $value): static
    {
        // Throws an exception when property is undefined
        if ($name !== '@context') {
            $this->has($name);
        }

        // Validate given value
        if (!Validator::validate($name, $value, $this)) {
            $message = "Rejected value. Type='%s', Property='%s', value='%s'";
            throw new Exception(
                sprintf(
                    $message,
                    static::class,
                    $name,
                    print_r($value, true)
                )
                . PHP_EOL
            );
        }

        // @context has a special role
        if ($name === '@context') {
            $this->_props[$name] = $value;

        // All modes and property defined
        } elseif ($this->has($name)) {
            $this->_props[$name] = $this->transform($value);

        // Undefined property but it's valid as it was
            // tested in the if clause above (no exception) so, let's include it
        } else {
            $this->_props[$name] = $this->transform($value);
        }

        return $this;
    }

    /**
     * Affect a value to a property or an extended property
     *
     * @param mixed $value
     *
     * @throws Exception
     *
     * @return mixed
     */
    private function transform(mixed $value): mixed
    {
        // Deep typing
        if (is_array($value)) {
            if (isset($value['type'])) {
                return Type::create($value);
            } elseif (is_int(key($value))) {
                return array_map(
                    static function ($value) {
                        return is_array($value) && isset($value['type'])
                            ? Type::create($value)
                            : $value;
                    },
                    $value
                );
            // Empty array, array that should not be cast as ActivityStreams types
            } else {
                return $value;
            }
        } else {
            // Scalars
            return $value;
        }
    }

    /**
     * Standard getter method
     *
     * @param string $name
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get(string $name): mixed
    {
        // Throws an exception when property is undefined
        $this->has($name);

        return $this->_props[$name];
    }

    /**
     * Checks that property exists
     *
     * @param string $name
     * @param bool   $strict
     *
     * @throws Exception
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        if (isset($this->{$name})) {
            if (!array_key_exists($name, $this->_props)) {
                $this->_props[$name] = $this->{$name};
            }

            return true;
        }

        if (array_key_exists($name, $this->_props)) {
            return true;
        }

        $reflect       = new ReflectionClass(Type::create($this->type));
        $allowed_props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        $allowed       = [];
        foreach ($allowed_props as $prop) {
            $allowed[] = $prop->getName();
        }
        if (!in_array($name, $allowed)) {
            sort($allowed);
            throw new Exception(
                sprintf(
                    'Property "%s" is not defined. Type="%s", ' .
                    'Class="%s"' . PHP_EOL . 'Allowed properties: %s',
                    $name,
                    $this->get('type'),
                    static::class,
                    implode(', ', $allowed)
                )
            );
        } else {
            return false;
        }
    }

    /**
     * Get a list of all properties names
     *
     * @return array
     */
    public function getProperties(): array
    {
        return array_values(
            array_unique(
                array_merge(
                    array_keys($this->_props),
                    array_keys(
                        array_diff_key(
                            get_object_vars($this),
                            ['_props' => '1']
                        )
                    )
                )
            )
        );
    }

    /**
     * Get a list of all properties and their values
     * as an associative array.
     * Null values are not returned.
     */
    public function toArray(): array
    {
        $keys = array_keys(
            array_filter(
                get_object_vars($this),
                static function ($value, $key): bool {
                    return !is_null($value) && $key !== '_props';
                },
                ARRAY_FILTER_USE_BOTH
            )
        );

        $stack = [];

        // native properties
        foreach ($keys as $key) {
            if ($this->{$key} instanceof self) {
                $stack[$key] = $this->{$key}->toArray();
            } elseif (!is_array($this->{$key})) {
                $stack[$key] = $this->{$key};
            } elseif (is_array($this->{$key})) {
                if (is_int(key($this->{$key}))) {
                    $stack[$key] = array_map(
                        static function ($value) {
                            return $value instanceof self
                                ? $value->toArray()
                                : $value;
                        },
                        $this->{$key}
                    );
                } else {
                    $stack[$key] = $this->{$key};
                }
            }
        }

        // _props
        foreach ($this->_props as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if ($value instanceof self) {
                $stack[$key] = $value->toArray();
            } elseif (!is_array($value)) {
                $stack[$key] = $value;
            } else {
                if (is_int(key($value))) {
                    $stack[$key] = array_map(
                        static function ($value) {
                            return $value instanceof self
                                ? $value->toArray()
                                : $value;
                        },
                        $value
                    );
                } else {
                    $stack[$key] = $value;
                }
            }
        }

        return $stack;
    }

    /**
     * Get a JSON
     *
     * @param null|int $options PHP JSON options
     *
     * @return string
     */
    public function toJson(?int $options = null): string
    {
        return json_encode(
            $this->toArray(),
            (int) $options
        );
    }

    /**
     * Get a copy of current object and return a new instance
     *
     * @throws Exception
     *
     * @return self A new instance of this object
     */
    public function copy(): self
    {
        return Type::create(
            $this->type,
            $this->toArray()
        );
    }

    /**
     * Extend current type properties
     *
     * @param string $property
     * @param mixed  $default
     *
     * @throws Exception
     */
    public function extend(string $property, mixed $default = null): void
    {
        if ($this->has($property)) {
            return;
        }

        if (!array_key_exists($property, $this->_props)) {
            $this->_props[$property] = $default;
        }
    }

    /**
     * Magical isset method
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name)
            || array_key_exists($name, $this->_props);
    }

    /**
     * Magical setter method
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws Exception
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Magical getter method
     *
     * @param string $name
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * Overloading methods
     *
     * @param string     $name
     * @param null|array $arguments
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __call(string $name, ?array $arguments = [])
    {
        // Getters
        if (str_starts_with($name, 'get')) {
            $attr = lcfirst(substr($name, 3));
            return $this->get($attr);
        }

        // Setters
        if (str_starts_with($name, 'set')) {
            if (count($arguments) === 1) {
                $attr = lcfirst(substr($name, 3));
                return $this->set($attr, $arguments[0]);
            } else {
                throw new Exception(
                    sprintf(
                        'Expected exactly one argument for method "%s()"',
                        $name
                    )
                );
            }
        }

        throw new Exception(
            sprintf(
                'Method "%s" is not defined',
                $name
            )
        );
    }
}
