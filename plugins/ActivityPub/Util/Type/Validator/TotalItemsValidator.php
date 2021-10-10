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
 * \Plugin\ActivityPub\Util\Type\Validator\TotalItemsValidator is a dedicated
 * validator for totalItems attribute.
 */
class TotalItemsValidator implements ValidatorInterface
{
    /**
     * Validate totalItems value
     *
     * @param mixed $container A Collection
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Container type is Collection
        Util::subclassOf($container, Collection::class, true);

        // Must be a non-negative integer
        return Util::validateNonNegativeInteger($value);
    }
}
