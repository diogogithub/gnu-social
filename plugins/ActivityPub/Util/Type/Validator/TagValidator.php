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

use Plugin\ActivityPub\Util\Type\ValidatorTools;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\TagValidator is a dedicated
 * validator for tag attribute.
 */
class TagValidator extends ValidatorTools
{
    /**
     * Validate a tag value
     *
     * @param mixed $value
     * @param mixed $container An Object type
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        if (!count($value)) {
            return true;
        }

        return $this->validateObjectCollection(
            $value,
            $this->getCollectionItemsValidator()
        );
    }
}
