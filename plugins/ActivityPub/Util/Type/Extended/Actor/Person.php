<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type\Extended\Actor;

use Plugin\ActivityPub\Util\Type\Extended\AbstractActor;

/**
 * \Plugin\ActivityPub\Util\Type\Extended\Actor\Person is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Represents an individual person.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-person
 */
class Person extends AbstractActor
{
    /**
     * @var string
     */
    protected string $type = 'Person';
}
