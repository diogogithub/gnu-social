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
use Plugin\ActivityPub\Util\Type\Core\Link;
use Plugin\ActivityPub\Util\Type\Core\ObjectType;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\UrlValidator is a dedicated
 * validator for url attribute.
 */
class UrlValidator implements ValidatorInterface
{
    /**
     * Validate url value
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
        // Validate that container is an ObjectType
        Util::subclassOf($container, ObjectType::class, true);

        // Must be a valid URL
        if (is_array($value) && is_int(key($value))) {
            foreach ($value as $key => $item) {
                if (!$this->validateUrlOrLink($item)) {
                    return false;
                }
            }

            return true;
        }

        return $this->validateUrlOrLink($value);
    }

    /**
     * Validate that a value is a Link or a URL
     *
     * @param Link|string $value
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function validateUrlOrLink(Link|string $value): bool
    {
        return Util::validateUrl($value)
            || Util::validateLink($value)
            || Util::validateMagnet($value);
    }
}
