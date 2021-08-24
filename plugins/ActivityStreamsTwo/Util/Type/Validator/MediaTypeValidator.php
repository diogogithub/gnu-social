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

use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\MediaTypeValidator is a dedicated
 * validator for mediaType attribute.
 */
class MediaTypeValidator implements ValidatorInterface
{
    /**
     * Validate a mediaType attribute value
     *
     * @param mixed $value
     * @param mixed $container
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        return is_null($value)
            || Util::validateMediaType($value);
    }
}
