<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util\Type\Extended\Activity;

use Plugin\ActivityStreamsTwo\Util\Type\Core\Activity;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Extended\Activity\Reject is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Indicates that the actor is rejecting the object.
 * The target and origin typically have no defined meaning.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-reject
 */
class Reject extends Activity
{
    /**
     * @var string
     */
    protected string $type = 'Reject';
}
