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
 * \Plugin\ActivityPub\Util\Type\Validator\DeletedValidator is a dedicated
 * validator for deleted attribute.
 */
class DeletedValidator implements ValidatorInterface
{
    /**
     * Validate a DELETED attribute value
     *
     * @param mixed $value
     * @param mixed $container A Tombstone type
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Tombstone type
        Util::subclassOf($container, Tombstone::class, true);

        if (is_string($value)) {
            // MUST be a datetime
            if (Util::validateDatetime($value)) {
                return true;
            }
        }

        return false;
    }
}
