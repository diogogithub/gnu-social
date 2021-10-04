<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type\Validator;

use Exception;
use Plugin\ActivityPub\Util\Type\Core\Activity;
use Plugin\ActivityPub\Util\Type\Core\ObjectType;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\ObjectValidator is a dedicated
 * validator for object attribute.
 */
class ObjectValidator implements ValidatorInterface
{
    /**
     * Validate an object value
     *
     * @param mixed $value
     * @param mixed $container
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Container is an ObjectType or a Link
        Util::subclassOf(
            $container,
            [Activity::class],
            true
        );

        // URL
        if (is_string($value)) {
            return Util::validateUrl($value)
                || Util::validateOstatusTag($value);
        }

        if (is_array($value)) {
            $value = Util::arrayToType($value);
        }

        // Link or Object
        if (is_object($value)) {
            return Util::validateLink($value)
                || Util::isObjectType($value);
        }

        // Collection
        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_string($item) && Util::validateUrl($item)) {
                    continue;
                }

                if (is_array($item)) {
                    $item = Util::arrayToType($item);
                }

                if (is_object($item)
                    && Util::subclassOf($item, [ObjectType::class], true)) {
                    continue;
                }

                return false;
            }

            return count($value) > 0;
        }

        return false;
    }
}
