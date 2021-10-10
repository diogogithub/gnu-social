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

namespace Plugin\ActivityPub\Util\Type;

/**
 * \Plugin\ActivityPub\Util\Type\ValidatorInterface specifies methods that must be
 * implemented for attribute (property) validation.
 */
interface ValidatorInterface
{
    public function validate(mixed $value, mixed $container): bool;
}
