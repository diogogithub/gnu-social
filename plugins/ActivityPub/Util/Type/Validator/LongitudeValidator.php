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
use Plugin\ActivityPub\Util\Type\Extended\Object\Place;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\LongitudeValidator is a dedicated
 * validator for longitude attribute.
 */
class LongitudeValidator implements ValidatorInterface
{
    /**
     * Validate a longitude value
     *
     * @param mixed $value
     * @param mixed $container An object
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Place
        Util::subclassOf($container, Place::class, true);

        return Util::between($value, -180, 180);
    }
}
