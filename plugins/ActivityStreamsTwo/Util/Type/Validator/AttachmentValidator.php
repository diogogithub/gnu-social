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
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\AttachmentValidator is a dedicated
 * validator for attachment attribute.
 */
class AttachmentValidator extends ValidatorTools
{
    /**
     * Validate an attachment value
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
        if (is_array($value) && !count($value)) {
            return true;
        }

        return $this->validateListOrObject(
            $value,
            $container,
            $this->getAttachmentValidator()
        );
    }
}
