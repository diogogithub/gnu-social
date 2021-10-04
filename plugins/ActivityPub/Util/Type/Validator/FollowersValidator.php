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
use Plugin\ActivityPub\Util\Type\Core\Collection;
use Plugin\ActivityPub\Util\Type\Core\OrderedCollection;
use Plugin\ActivityPub\Util\Type\Extended\AbstractActor;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\FollowersValidator is a dedicated
 * validator for followers attribute.
 */
class FollowersValidator implements ValidatorInterface
{
    /**
     * Validate a FOLLOWERS attribute value
     *
     * @param mixed $value
     * @param mixed $container
     *
     * @throws Exception
     *
     * @return bool
     *
     * @todo Support indirect reference for followers attribute?
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is an AbstractActor type
        Util::subclassOf($container, AbstractActor::class, true);

        if (is_string($value)) {
            return Util::validateUrl($value);
        }

        // A collection
        return is_object($value) && $this->validateObject($value);
    }

    /**
     * Validate that it is an OrderedCollection or a Collection
     *
     * @param object $collection
     *
     * @throws Exception
     * @throws Exception
     *
     * @return bool
     */
    protected function validateObject(object $collection): bool
    {
        return Util::subclassOf(
                $collection,
                OrderedCollection::class
            ) || Util::subclassOf(
                $collection,
                Collection::class
            );
    }
}
