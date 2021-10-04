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
use Plugin\ActivityPub\Util\Type\Core\Link;
use Plugin\ActivityPub\Util\Type\Core\ObjectType;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorTools;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\TypeValidator is a dedicated
 * validator for type attribute.
 */
class TypeValidator extends ValidatorTools
{
    /**
     * Validate a type value
     *
     * @param mixed $value
     * @param mixed $container An Object type
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is an ObjectType or a Link
        Util::subclassOf(
            $container,
            [ObjectType::class, Link::class],
            true
        );

        return $this->validateString(
            $value
        );
    }
}
