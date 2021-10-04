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
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\OriginValidator is a dedicated
 * validator for origin attribute.
 */
class OriginValidator implements ValidatorInterface
{
    /**
     * Validate an origin value
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
        // Container is an Activity
        Util::subclassOf(
            $container,
            [Activity::class],
            true
        );

        // URL
        if (is_string($value)) {
            return Util::validateUrl($value);
        }

        if (is_array($value)) {
            $value = Util::arrayToType($value);
        }

        // Link or Object
        if (is_object($value)) {
            return Util::validateLink($value)
                || Util::isObjectType($value);
        }

        return false;
    }
}
