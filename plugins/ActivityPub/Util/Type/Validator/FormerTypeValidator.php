<?php

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
use Plugin\ActivityPub\Util\Type\Extended\Object\Tombstone;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\FormerTypeValidator is a dedicated
 * validator for formerType attribute.
 */
class FormerTypeValidator implements ValidatorInterface
{
    /**
     * Validate a formerType attribute value
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
        // Validate that container has a Tombstone type
        Util::subclassOf($container, Tombstone::class, true);

        if (is_array($value)) {
            $value = Util::arrayToType($value);
        }

        // MUST be a valid Object type
        return Util::isObjectType($value);
    }
}
