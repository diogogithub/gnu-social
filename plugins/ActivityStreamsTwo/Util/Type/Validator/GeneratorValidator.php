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
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\GeneratorValidator is a dedicated
 * validator for generator attribute.
 */
class GeneratorValidator implements ValidatorInterface
{
    /**
     * Validate a generator attribute value
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
        // Validate that container has an ObjectType type
        Util::subclassOf($container, ObjectType::class, true);

        if (Util::validateUrl($value)) {
            return true;
        }

        if (is_array($value)) {
            $value = Util::arrayToType($value);
        }

        // MUST be a valid Actor type
        return Util::isActorType($value) || Util::validateLink($value);
    }
}
