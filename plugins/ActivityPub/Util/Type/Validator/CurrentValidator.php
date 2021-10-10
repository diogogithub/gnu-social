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
use Plugin\ActivityPub\Util\Type\Core\Collection;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\CurrentValidator is a dedicated
 * validator for current attribute.
 */
class CurrentValidator implements ValidatorInterface
{
    /**
     * Validate a current attribute value
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Container must be a Collection
        Util::subclassOf($container, Collection::class, true);

        // URL
        if (Util::validateUrl($value)) {
            return true;
        }

        // Link or CollectionPage
        return Util::validateLink($value)
            || Util::validateCollectionPage($value);
    }
}
