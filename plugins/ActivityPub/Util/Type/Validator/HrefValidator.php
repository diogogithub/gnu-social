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
use Plugin\ActivityPub\Util\Type\Core\ObjectType;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\HrefValidator is a dedicated
 * validator for href attribute.
 */
class HrefValidator implements ValidatorInterface
{
    /**
     * Validate href value
     *
     * @param mixed $container An object
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Link or an Object
        Util::subclassOf(
            $container,
            [Link::class, ObjectType::class],
            true,
        );

        // Must be a valid URL or a valid magnet link
        return Util::validateUrl($value)
            || Util::validateMagnet($value);
    }
}
