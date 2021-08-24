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
use Plugin\ActivityStreamsTwo\Util\Type\Core\Link;
use Plugin\ActivityStreamsTwo\Util\Type\Extended\Object\Image;
use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\HeightValidator is a dedicated
 * validator for height attribute.
 */
class HeightValidator implements ValidatorInterface
{
    /**
     * Validate height value
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
        // Validate that container is a Link
        Util::subclassOf($container, [Link::class, Image::class], true);

        // Must be a non-negative integer
        return Util::validateNonNegativeInteger($value);
    }
}
