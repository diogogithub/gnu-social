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
use Plugin\ActivityPub\Util\Type\Core\ObjectType;
use Plugin\ActivityPub\Util\Type\Extended\Object\Profile;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\DescribesValidator is a dedicated
 * validator for describes attribute.
 */
class DescribesValidator implements ValidatorInterface
{
    /**
     * Validate an DESCRIBES attribute value
     *
     * @param mixed $value
     * @param mixed $container A Profile type
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Tombstone type
        Util::subclassOf($container, Profile::class, true);

        if (is_object($value)) {
            // MUST be an Object
            return Util::subclassOf($value, ObjectType::class, true);
        }

        return false;
    }
}
