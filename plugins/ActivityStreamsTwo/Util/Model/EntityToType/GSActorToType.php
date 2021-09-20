<?php

namespace Plugin\ActivityStreamsTwo\Util\Model\EntityToType;

use App\Core\Router\Router;
use App\Entity\Actor;
use Component\Avatar\Avatar;
use Component\Avatar\Exception\NoAvatarException;
use DateTimeInterface;
use Exception;
use Plugin\ActivityStreamsTwo\Util\Type;

class GSActorToType
{
    /**
     * @param Actor $gsactor
     *
     *@throws Exception
     *
     * @return Type
     *
     */
    public static function translate(Actor $gsactor)
    {
        $uri  = Router::url('actor_view_id', ['id' => $gsactor->getId()], Router::ABSOLUTE_URL);
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
