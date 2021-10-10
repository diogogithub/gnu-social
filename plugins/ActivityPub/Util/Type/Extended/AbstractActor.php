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

namespace Plugin\ActivityPub\Util\Type\Extended;

use Plugin\ActivityPub\Util\Type\Core\ObjectType;
use Plugin\ActivityPub\Util\Type\Core\OrderedCollection;

/**
 * \ActivityPhp\Type\Extended\AbstractActor is an abstract class that
 * provides dedicated Actor's properties
 */
abstract class AbstractActor extends ObjectType
{
    /**
     * A reference to an ActivityStreams OrderedCollection comprised of
     * all the messages received by the actor.
     *
     * @see https://www.w3.org/TR/activitypub/#inbox
     *
     * @var OrderedCollection
     *                        | \ActivityPhp\Type\Core\OrderedCollectionPage
     *                        | null
     */
    protected OrderedCollection $inbox;

    /**
     * A reference to an ActivityStreams OrderedCollection comprised of
     * all the messages produced by the actor.
     *
     * @see https://www.w3.org/TR/activitypub/#outbox
     *
     * @var OrderedCollection
     *                        | \ActivityPhp\Type\Core\OrderedCollectionPage
     *                        | null
     */
    protected OrderedCollection $outbox;

    /**
     * A link to an ActivityStreams collection of the actors that this
     * actor is following.
     *
     * @see https://www.w3.org/TR/activitypub/#following
     */
    protected string $following;

    /**
     * A link to an ActivityStreams collection of the actors that
     * follow this actor.
     *
     * @see https://www.w3.org/TR/activitypub/#followers
     */
    protected string $followers;

    /**
     * A link to an ActivityStreams collection of objects this actor has
     * liked.
     *
     * @see https://www.w3.org/TR/activitypub/#liked
     */
    protected string $liked;

    /**
     * A list of supplementary Collections which may be of interest.
     *
     * @see https://www.w3.org/TR/activitypub/#streams-property
     */
    protected array $streams = [];

    /**
     * A short username which may be used to refer to the actor, with no
     * uniqueness guarantees.
     *
     * @see https://www.w3.org/TR/activitypub/#preferredUsername
     */
    protected ?string $preferredUsername;

    /**
     * A JSON object which maps additional typically server/domain-wide
     * endpoints which may be useful either for this actor or someone
     * referencing this actor. This mapping may be nested inside the
     * actor document as the value or may be a link to a JSON-LD
     * document with these properties.
     *
     * @see https://www.w3.org/TR/activitypub/#endpoints
     */
    protected string|array|null $endpoints;

    /**
     * It's not part of the ActivityPub protocol, but it's a quite common
     * practice handling an actor public key with a publicKey array:
     * [
     *     'id' => 'https://my-example.com/actor#main-key'
     *     'owner' => 'https://my-example.com/actor',
     *     'publicKeyPem' => '-----BEGIN PUBLIC KEY-----
     *                       MIIBI [...]
     *                       DQIDAQAB
     *                       -----END PUBLIC KEY-----'
     * ]
     *
     * @see https://www.w3.org/wiki/SocialCG/ActivityPub/Authentication_Authorization#Signing_requests_using_HTTP_Signatures
     */
    protected string|array|null $publicKey;
}
