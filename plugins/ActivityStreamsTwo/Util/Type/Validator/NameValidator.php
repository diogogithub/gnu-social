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
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\NameValidator is a dedicated
 * validator for name attribute.
 */
class NameValidator implements ValidatorInterface
{
    /**
     * Validate a name attribute value
     *
     * @param mixed $value
     * @param mixed $container
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return Util::validatePlainText($value);
    }
}
