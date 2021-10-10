<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\EntityToType;

use App\Core\Event;
use App\Core\Router\Router;
use App\Entity\Actor;
use Component\Avatar\Avatar;
use Component\Avatar\Exception\NoAvatarException;
use DateTimeInterface;
use Exception;
use Plugin\ActivityPub\Util\Type;

class GSActorToType
{
    /**
     *@throws Exception
     */
    public static function translate(Actor $gsactor): Type
    {
        $uri = null;
        Event::handle('FreeNetworkGenerateLocalActorUri', ['source' => 'ActivityPub', 'actor_id' => $gsactor->getId(), 'actor_uri' => &$attributedTo]);
        $attr = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => $uri,
            'inbox'     => Router::url('activitypub_actor_inbox', ['gsactor_id' => $gsactor->getId()], Router::ABSOLUTE_URL),
            'outbox'    => Router::url('activitypub_actor_outbox', ['gsactor_id' => $gsactor->getId()], Router::ABSOLUTE_URL),
            'following' => Router::url('actor_subscriptions_id', ['id' => $gsactor->getId()], Router::ABSOLUTE_URL),
            'followers' => Router::url('actor_subscribers_id', ['id' => $gsactor->getId()], Router::ABSOLUTE_URL),
            'liked'     => Router::url('actor_favourites_id', ['id' => $gsactor->getId()], Router::ABSOLUTE_URL),
            //'streams' =>
            'preferredUsername' => $gsactor->getNickname(),
            //'publicKey' => [
            //                'id' => $uri . "#public-key",
            //                'owner' => $uri,
            //                'publicKeyPem' => $public_key
            //            ],
            'name'      => $gsactor->getFullname(),
            'location'  => $gsactor->getLocation(),
            'published' => $gsactor->getCreated()->format(DateTimeInterface::RFC3339),
            'summary'   => $gsactor->getBio(),
            //'tag' => $gsactor->getSelfTags(),
            'updated' => $gsactor->getModified()->format(DateTimeInterface::RFC3339),
            'url'     => Router::url('actor_view_nickname', ['nickname' => $gsactor->getNickname()], Router::ABSOLUTE_URL),
        ];
        try {
            $attr['icon'] = Avatar::getAvatar($gsactor->getId())->getUrl(type: Router::ABSOLUTE_URL);
        } catch (NoAvatarException) {
            // No icon for this actor
        }

        return Type::create(type: 'Person', attributes: $attr);
    }
}
