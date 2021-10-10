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
use Plugin\ActivityPub\Util\Type\Extended\AbstractActor;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\ActorValidator is a dedicated
 * validator for actor attribute.
 */
class ActorValidator implements ValidatorInterface
{
    /**
     * Validate an ACTOR attribute value
     *
     * @param mixed $container An object
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Can be an indirect link
        if (\is_string($value) && Util::validateUrl($value)) {
            return true;
        }

        if (\is_array($value)) {
            $value = Util::arrayToType($value);
        }

        // A collection
        if (\is_array($value)) {
            return $this->validateObjectCollection($value);
        }

        // Must be an object
        if (!\is_object($value)) {
            return false;
        }

        // A single actor
        return $this->validateObject($value);
    }

    /**
     * Validate an Actor object type
     *
     * @throws Exception
     */
    protected function validateObject(object|array $item): bool
    {
        if (\is_array($item)) {
            $item = Util::arrayToType($item);
        }

        Util::subclassOf(
            $item,
            [
                AbstractActor::class,
                Link::class,
                Collection::class,
            ],
            true,
        );

        return true;
    }

    /**
     * Validate a list of object
     * Collection can contain:
     * - Indirect URL
     * - An actor object
     *
     * @throws Exception
     * @throws Exception
     */
    protected function validateObjectCollection(array $collection): bool
    {
        foreach ($collection as $item) {
            if (\is_array($item) && $this->validateObject($item)) {
                continue;
            }
            if (\is_object($item) && $this->validateObject($item)) {
                continue;
            }
            if (\is_string($item) && Util::validateUrl($item)) {
                continue;
            }

            return false;
        }

        return \count($collection) > 0;
    }
}
