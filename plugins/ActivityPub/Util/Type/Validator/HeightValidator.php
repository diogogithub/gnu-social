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
use Plugin\ActivityPub\Util\Type\Core\Link;
use Plugin\ActivityPub\Util\Type\Extended\Object\Image;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\HeightValidator is a dedicated
 * validator for height attribute.
 */
class HeightValidator implements ValidatorInterface
{
    /**
     * Validate height value
     *
     * @param mixed $container An object
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Link
        Util::subclassOf($container, [Link::class, Image::class], true);

        // Must be a non-negative integer
        return Util::validateNonNegativeInteger($value);
    }
}
