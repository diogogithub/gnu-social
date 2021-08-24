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
 * \Plugin\ActivityStreamsTwo\Util\Type\Extended\Activity\Like is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Indicates that the actor likes, recommends or endorses the object.
 * The target and origin typically have no defined meaning.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-like
 */
class Like extends Activity
{
    /**
     * @var string
     */
    protected string $type = 'Like';
}
