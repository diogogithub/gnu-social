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
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorTools;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\BtoValidator is a dedicated
 * validator for bto attribute.
 */
class BtoValidator extends ValidatorTools
{
    /**
     * Validate a bto value
     *
     * @param mixed $value
     * @param mixed $container An Object type
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        return $this->validateListOrObject(
            $value,
            $container,
            $this->getLinkOrUrlObjectValidator()
        );
    }
}
