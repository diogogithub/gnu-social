<?php

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
 * \Plugin\ActivityPub\Util\Type\Extended\Object\Tombstone is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * A Tombstone represents a content object that has been deleted. It can
 * be used in Collections to signify that there used to be an object at
 * this position, but it has been deleted.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-tombstone
 */
class Tombstone extends ObjectType
{
    /**
     * @var string
     */
    protected string $type = 'Tombstone';

    /**
     * The type of the object that was deleted.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-formertype
     *
     * @var null|string
     */
    protected ?string $formerType;

    /**
     * A timestamp for when the object was deleted.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-deleted
     *
     * @var null|string xsd:dateTime formatted
     */
    protected ?string $deleted;
}
