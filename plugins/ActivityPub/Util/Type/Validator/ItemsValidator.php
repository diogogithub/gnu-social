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
use Plugin\ActivityPub\Util\Type\Core\Link;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorTools;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\ItemsValidator is a dedicated
 * validator for items attribute.
 */
class ItemsValidator extends ValidatorTools
{
    /**
     * Validate items value
     *
     * @param mixed $container A Collection type
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Collection
        Util::subclassOf(
            $container,
            [Collection::class],
            true,
        );

        // URL type
        if (\is_string($value)) {
            return Util::validateUrl($value);
        }

        if (\is_array($value)) {
            // Empty array
            if (!\count($value)) {
                return true;
            }
            $value = Util::arrayToType($value);
        }

        // Link type
        if (\is_object($value)) {
            return Util::subclassOf($value, Link::class, true);
        }

        // A Collection
        if (!\is_array($value)) {
            return false;
        }

        if (!\count($value)) {
            return false;
        }

        return $this->validateObjectCollection(
            $value,
            $this->getCollectionItemsValidator(),
        );
    }
}
