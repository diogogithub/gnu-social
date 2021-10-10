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

namespace Plugin\ActivityPub\Util\Type\Extended\Object;

use Plugin\ActivityPub\Util\Type\Core\ObjectType;

/**
 * \Plugin\ActivityPub\Util\Type\Extended\Object\Profile is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * A Profile is a content object that describes another Object,
 * typically used to describe Actor Type objects.
 * The describes property is used to reference the object being
 * described by the profile.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-profile
 */
class Profile extends ObjectType
{
    protected string $type = 'Profile';

    /**
     * Identify the object described by the Profile.
     */
    protected ObjectType $describes;
}
