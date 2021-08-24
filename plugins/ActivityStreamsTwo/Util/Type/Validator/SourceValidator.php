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
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\SourceValidator is a dedicated
 * validator for source attribute.
 */
class SourceValidator implements ValidatorInterface
{
    /**
     * Validate source value
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
        // Container is an ObjectType
        Util::subclassOf(
            $container,
            ObjectType::class,
            true
        );

        if (is_array($value)) {
            $value = (object) $value;
        }

        if (is_object($value)) {
            return Util::hasProperties(
                $value,
                ['content', 'mediaType'],
                true
            );
        }

        return false;
    }
}
