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
 * \Plugin\ActivityPub\Util\Type\Validator\EndTimeValidator is a dedicated
 * validator for endTime attribute.
 */
class EndTimeValidator implements ValidatorInterface
{
    /**
     * Validate an ENDTIME attribute value
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container has an ObjectType type
        Util::subclassOf($container, ObjectType::class, true);

        // MUST be a valid xsd:dateTime
        return Util::validateDatetime($value);
    }
}