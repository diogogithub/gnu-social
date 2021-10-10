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

namespace Plugin\ActivityPub\Util\Type\Extended\Activity;

use Plugin\ActivityPub\Util\Type\Core\Activity;

/**
 * \Plugin\ActivityPub\Util\Type\Extended\Activity\Undo is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Indicates that the actor is undoing the object. In most cases, the
 * object will be an Activity describing some previously performed
 * action (for instance, a person may have previously "liked" an article
 * but, for whatever reason, might choose to undo that like at some
 * later point in time).
 *
 * The target and origin typically have no defined meaning.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-undo
 */
class Undo extends Activity
{
    protected string $type = 'Undo';
}
