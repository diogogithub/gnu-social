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
use Plugin\ActivityPub\Util\Type\ValidatorTools;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\NameMapValidator is a dedicated
 * validator for nameMap attribute.
 */
class NameMapValidator extends ValidatorTools
{
    /**
     * Validate a nameMap value
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        return $this->validateMap('name', $value, $container);
    }
}
