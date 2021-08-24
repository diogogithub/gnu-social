<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util\Type\Core;

/**
 * CollectionPage is an implementation of one
 * of the Activity Streams Core Types.
 *
 * Used to represent distinct subsets of items from a Collection.
 *
 * @see https://www.w3.org/TR/activitystreams-core/#paging
 */
class CollectionPage extends Collection
{
    /**
     * @var string
     */
    protected string $type = 'CollectionPage';

    /**
     * @var string
     */
    protected string $id;

    /**
     * Identifies the Collection to which CollectionPage objects items
     * belong.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-partof
     *
     * @var string
     *             | Link
     *             | Collection
     *             | null
     */
    protected string $partOf;

    /**
     * Indicates the next page of items.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-next
     *
     * @var string
     *             | Link
     *             | CollectionPage
     *             | null
     */
    protected string $next;

    /**
     * Identifies the previous page of items.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-prev
     *
     * @var string
     *             | Link
     *             | CollectionPage
     *             | null
     */
    protected string $prev;
}
