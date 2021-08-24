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
use Plugin\ActivityStreamsTwo\Util\Type\Core\ObjectType;
use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\SummaryValidator is a dedicated
 * validator for summary attribute.
 */
class SummaryValidator implements ValidatorInterface
{
    /**
     * Validate a summary attribute value
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
        // Validate that container is an ObjectType type
        Util::subclassOf($container, ObjectType::class, true);

        // Must be a string
        return is_null($value) || is_string($value);
    }
}
