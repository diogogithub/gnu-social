<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type\Core;

/**
 * \Plugin\ActivityPub\Util\Type\Core\IntransitiveActivity is an implementation of
 * one of the Activity Streams Core Types.
 *
 * IntransitiveActivity objects are specializations of the Activity type
 * that represent intransitive actions. IntransitiveActivity objects do
 * not have an object property.
 *
 * @see https://www.w3.org/TR/activitystreams-core/#intransitiveactivities
 */
class IntransitiveActivity extends AbstractActivity
{
    /**
     * @var string
     */
    protected string $type = 'IntransitiveActivity';
}
