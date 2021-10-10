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

namespace Plugin\ActivityPub\Util\Type\Core;

/**
 * \Plugin\ActivityPub\Util\Type\Core\Collection is an implementation of one of the
 * Activity Streams Core Types.
 *
 * Collection objects are a specialization of the base Object that serve
 * as a container for other Objects or Links.
 *
 * @see https://www.w3.org/TR/activitystreams-core/#collections
 */
class Collection extends ObjectType
{
    protected string $type = 'Collection';

    public string $id;

    /**
     * A non-negative integer specifying the total number of objects
     * contained by the logical view of the collection.
     * This number might not reflect the actual number of items
     * serialized within the Collection object instance.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-totalitems
     */
    protected int $totalItems;

    /**
     * In a paged Collection, indicates the page that contains the most
     * recently updated member items.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-current
     *
     * @var string
     *             | Link
     *             | CollectionPage
     *             | null
     */
    protected string $current;

    /**
     * The furthest preceding page of items in the collection.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-last
     *
     * @var string
     *             | Link
     *             | CollectionPage
     *             | null
     */
    protected string $first;

    /**
     * The furthest proceeding page of the collection.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-last
     *
     * @var string
     *             | Link
     *             | CollectionPage
     *             | null
     */
    protected string $last;

    /**
     * The items contained in a collection.
     * The items are considered as unordered.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-items
     *
     * @var array
     *            | Link
     *            | array<Link>
     *            | array<ObjectType>
     */
    protected array $items = [];

    /**
     * The items contained in a collection.
     * The items are considered as ordered.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-items
     *
     * @var array
     *            | Link
     *            | array<Link>
     *            | array<ObjectType>
     */
    protected array $orderedItems = [];
}
