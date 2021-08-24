<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util;

use Exception;
use Plugin\ActivityStreamsTwo\Util\Type\AbstractObject;
use Plugin\ActivityStreamsTwo\Util\Type\TypeResolver;
use Plugin\ActivityStreamsTwo\Util\Type\Validator;

/**
 * \ActivityPhp\Type is a Factory for ActivityStreams 2.0 types.
 *
 * It provides shortcuts methods for type instantiation and more.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#types
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#activity-types
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#actor-types
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#object-types
 */
abstract class Type
{
    /**
     * Factory method to create type instance and set attributes values
     *
     * To see which default types are defined and their attributes:
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#types
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#activity-types
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#actor-types
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#object-types
     *
     * @param array<string,mixed>|string $type
     * @param array<string,mixed>        $attributes
     *
     * @throws Exception
     *
     * @return mixed
     */
    public static function create($type, array $attributes = [])
    {
        if (!is_string($type) && !is_array($type)) {
            throw new Exception(
                'Type parameter must be a string or an array. Given='
                . gettype($type)
            );
        }

        if (is_array($type)) {
            if (!isset($type['type'])) {
                throw new Exception(
                    "Type parameter must have a 'type' key"
                );
            }

            $attributes = $type;
        }

        try {
            $class = is_array($type)
                ? TypeResolver::getClass($type['type'])
                : TypeResolver::getClass($type);
        } catch (Exception $exception) {
            $message = json_encode($attributes, JSON_PRETTY_PRINT);
            throw new Exception(
                $exception->getMessage() . "\n{$message}"
            );
        }

        if (is_string($class)) {
            $class = new $class();
        }

        foreach ($attributes as $name => $value) {
            try {
                $class->set($name, $value);
            } catch (Exception) {
                // Discard invalid properties
            }
        }

        return $class;
    }

    /**
     * Create an activitystream type from a JSON string
     */
    public static function fromJson(string $json): AbstractObject
    {
        $data = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE
            && is_array($data)
        ) {
            return self::create($data);
        }

        throw new Exception(
            sprintf(
                "An error occurred during the JSON decoding.\n '%s'",
                $json
            )
        );
    }

    /**
     * Add a custom validator for an attribute.
     * It checks that it implements Validator\Interface
     *
     * @param string $name  An attribute name to validate.
     * @param string $class A validator class name
     */
    public static function addValidator(string $name, string $class): void
    {
        Validator::add($name, $class);
    }
}
