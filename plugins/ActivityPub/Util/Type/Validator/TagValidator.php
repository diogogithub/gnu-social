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
     * @param mixed $container An Object type
     */
    public function validate(mixed $value, mixed $container): bool
    {
        if (!\count($value)) {
            return true;
        }

        return $this->validateObjectCollection(
            $value,
            $this->getCollectionItemsValidator(),
        );
    }
}
