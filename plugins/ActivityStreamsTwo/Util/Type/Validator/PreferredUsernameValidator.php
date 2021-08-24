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
use Plugin\ActivityStreamsTwo\Util\Type\Extended\AbstractActor;
use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorTools;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\PreferredUsernameValidator is a dedicated
 * validator for preferredUsername attribute.
 */
class PreferredUsernameValidator extends ValidatorTools
{
    /**
     * Validate preferredUsername value
     *
     * @param mixed $value
     * @param mixed $container An Actor
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is an Actor
        Util::subclassOf($container, AbstractActor::class, true);

        return $this->validateString(
            $value
        );
    }
}
