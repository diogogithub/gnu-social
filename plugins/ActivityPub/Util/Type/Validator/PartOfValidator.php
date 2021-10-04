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
use Plugin\ActivityPub\Util\Type\Core\CollectionPage;
use Plugin\ActivityPub\Util\Type\Core\OrderedCollectionPage;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\PartOfValidator is a dedicated
 * validator for partOf attribute.
 */
class PartOfValidator implements ValidatorInterface
{
    /**
     * Validate a partOf value
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
        // Container is CollectionPage or OrderedCollectionPage type
        Util::subclassOf(
            $container, [
                CollectionPage::class, OrderedCollectionPage::class,
            ], true
        );

        // URL
        if (is_string($value)) {
            return Util::validateUrl($value);
        }

        if (is_array($value)) {
            $value = Util::arrayToType($value);
        }

        // Link or Collection
        if (is_object($value)) {
            return Util::validateLink($value)
                || Util::validateCollection($value);
        }

        return false;
    }
}
