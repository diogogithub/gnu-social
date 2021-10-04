<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type\Extended\Activity;

use Plugin\ActivityPub\Util\Type\Core\Activity;

/**
 * \Plugin\ActivityPub\Util\Type\Extended\Activity\Accept is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Indicates that the actor accepts the object. The target property can
 * be used in certain circumstances to indicate the context into which
 * the object has been accepted.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-accept
 */
class Accept extends Activity
{
    /**
     * @var string
     */
    protected string $type = 'Accept';
}
