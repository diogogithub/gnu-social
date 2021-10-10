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
use Plugin\ActivityPub\Util\Type\Core\Link;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\HreflangValidator is a dedicated
 * validator for hreflang attribute.
 */
class HreflangValidator implements ValidatorInterface
{
    /**
     * Validate href value
     *
     * @param mixed $container An object
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is a Link
        Util::subclassOf($container, Link::class, true);

        // Must be a valid URL
        return Util::validateBcp47($value);
    }
}
