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
use Plugin\ActivityPub\Util\Type\Core\ObjectType;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\DurationValidator is a dedicated
 * validator for duration attribute.
 */
class DurationValidator implements ValidatorInterface
{
    /**
     * Validate an DURATION attribute value
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container has an ObjectType type
        Util::subclassOf($container, ObjectType::class, true);

        if (\is_string($value)) {
            // MUST be an XML 8601 Duration formatted string
            return Util::isDuration($value, true);
        }

        return false;
    }
}
