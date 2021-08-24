<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util\Type\Validator;

use Exception;
use Plugin\ActivityStreamsTwo\Util\Type\Extended\Object\Place;
use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\UnitsValidator is a dedicated
 * validator for units attribute.
 */
class UnitsValidator implements ValidatorInterface
{
    /**
     * Validate units value
     *
     * @param mixed $value
     * @param mixed $container A Place
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Container must be Place
        Util::subclassOf($container, Place::class, true);

        // Must be a valid units
        return Util::validateUnits($value);
    }
}
