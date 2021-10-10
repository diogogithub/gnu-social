<?php

declare(strict_types = 1);

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type;

use Exception;

/**
 * \Plugin\ActivityPub\Util\Type\Validator is an abstract class for
 * attribute validation.
 */
abstract class Validator
{
    /**
     * Contains all custom validators
     *
     * @var array<string, ValidatorInterface>
     *
     * [ 'attributeName' => CustomValidatorClassName::class ]
     */
    protected static array $validators = [];

    /**
     * Validate an attribute value for given attribute name and
     * container object.
     *
     * @param mixed $container An object
     *
     * @throws Exception if $container is not an object
     */
    public static function validate(string $name, mixed $value, mixed $container): bool
    {
        if (!\is_object($container)) {
            throw new Exception(
                'Given container is not an object',
            );
        }

        // Perform validation
        if (isset(self::$validators[$name])) {
            return self::$validators[$name]->validate(
                $value,
                $container,
            );
        }

        // Try to load a default validator
        $validatorName = sprintf(
            '\Plugin\ActivityPub\Util\Type\Validator\%sValidator',
            ucfirst($name),
        );

        if (class_exists($validatorName)) {
            self::add($name, $validatorName);
            return self::validate($name, $value, $container);
        }

        // There is no validator for this attribute
        return true;
    }

    /**
     * Add a new validator in the pool.
     * It checks that it implements Validator\Interface
     *
     * @param string        $name  an attribute name to validate
     * @param object|string $class A validator class name
     *
     * @throws Exception if validator class does not implement
     *                   \Plugin\ActivityPub\Util\Type\Helper\ValidatorInterface
     */
    public static function add(string $name, object|string $class): void
    {
        $validator = new $class();

        if (!($validator instanceof ValidatorInterface)) {
            throw new Exception(
                sprintf(
                    'Validator "%s" MUST implement "%s" interface',
                    \get_class($validator),
                    ValidatorInterface::class,
                ),
            );
        }

        self::$validators[$name] = $validator;
    }
}
