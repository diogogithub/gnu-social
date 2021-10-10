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

use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\ContentValidator is a dedicated
 * validator for content attribute.
 */
class ContentValidator implements ValidatorInterface
{
    /**
     * Validate a content attribute value
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Must be a string or null
        return (bool) (\is_null($value) || \is_string($value));
    }
}
