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

namespace Plugin\ActivityPub\Util\Type\Validator;

use Exception;
use Plugin\ActivityPub\Util\Type\Extended\Object\Place;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\UnitsValidator is a dedicated
 * validator for units attribute.
 */
class UnitsValidator implements ValidatorInterface
{
    /**
     * Validate units value
     *
     * @param mixed $container A Place
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Container must be Place
        Util::subclassOf($container, Place::class, true);

        // Must be a valid units
        return Util::validateUnits($value);
    }
}
