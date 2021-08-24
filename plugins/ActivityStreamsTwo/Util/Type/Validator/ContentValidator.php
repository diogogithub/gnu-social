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

use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\ContentValidator is a dedicated
 * validator for content attribute.
 */
class ContentValidator implements ValidatorInterface
{
    /**
     * Validate a content attribute value
     *
     * @param mixed $value
     * @param mixed $container
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Must be a string or null
        if (is_null($value) || is_string($value)) {
            return true;
        }

        return false;
    }
}
