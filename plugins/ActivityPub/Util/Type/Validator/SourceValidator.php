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
 * \Plugin\ActivityPub\Util\Type\Validator\SourceValidator is a dedicated
 * validator for source attribute.
 */
class SourceValidator implements ValidatorInterface
{
    /**
     * Validate source value
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Container is an ObjectType
        Util::subclassOf(
            $container,
            ObjectType::class,
            true,
        );

        if (\is_array($value)) {
            $value = (object) $value;
        }

        if (\is_object($value)) {
            return Util::hasProperties(
                $value,
                ['content', 'mediaType'],
                true,
            );
        }

        return false;
    }
}
