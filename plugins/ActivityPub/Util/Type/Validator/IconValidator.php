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
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\IconValidator is a dedicated
 * validator for icon attribute.
 */
class IconValidator implements ValidatorInterface
{
    /**
     * Validate icon item
     *
     * @param mixed $value
     * @param mixed $container An object
     *
     * @throws Exception
     *
     * @return bool
     *
     * @todo Implement size checks
     * @todo Support Image objects and Link objects
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a ObjectType
        Util::subclassOf($container, ObjectType::class, true);

        if (is_string($value)) {
            return Util::validateUrl($value);
        }

        if (is_array($value)) {
            $value = Util::arrayToType($value);
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_array($item)) {
                    $item = Util::arrayToType($item);
                }

                if (is_string($item) && Util::validateUrl($item)) {
                    continue;
                }

                if (!$this->validateObject($item)) {
                    return false;
                }
            }

            return true;
        }

        // Must be an Image or a Link
        return $this->validateObject($value);
    }

    /**
     * Validate an object format
     *
     * @param object $item
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function validateObject(object $item): bool
    {
        return Util::validateLink($item)
            || Util::isType($item, 'Image');
    }
}
