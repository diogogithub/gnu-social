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
use Plugin\ActivityStreamsTwo\Util\Type\Core\Collection;
use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\CurrentValidator is a dedicated
 * validator for current attribute.
 */
class CurrentValidator implements ValidatorInterface
{
    /**
     * Validate a current attribute value
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
