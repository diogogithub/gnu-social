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
use Plugin\ActivityStreamsTwo\Util\Type\Core\Link;
use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\RelValidator is a dedicated
 * validator for rel attribute.
 */
class RelValidator implements ValidatorInterface
{
    /**
     * Validate rel value
     *
     * @param mixed $value
     * @param mixed $container A Link
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Link
        Util::subclassOf($container, Link::class, true);

        // Must be a valid Rel
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (!is_int($key)
                    || !Util::validateRel($item)) {
                    return false;
                }
            }

            return true;
        }

        return Util::validateRel($value);
    }
}
