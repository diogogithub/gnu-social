<?php

namespace Plugin\ActivityStreamsTwo\Util\Model\EntityToType;

use App\Core\Router\Router;
use App\Entity\Actor;
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
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => $uri,
            //'inbox' =>
            //'outbox' =>
            //'following' =>
            //'followers' =>
            //'liked' =>
            //'streams' =>
            'preferredUsername' => $gsactor->getNickname(),
            //'publicKey' => [
            //                'id' => $uri . "#public-key",
            //                'owner' => $uri,
            //                'publicKeyPem' => $public_key
            //            ],
            'name' => $gsactor->getFullname(),
            //'icon' =>
            //'location' =>
            'published' => $gsactor->getCreated()->format(DateTimeInterface::RFC3339),
            'summary'   => $gsactor->getBio(),
            //'tag' =>
            'updated' => $gsactor->getModified()->format(DateTimeInterface::RFC3339),
            'url'     => Router::url('actor_view_nickname', ['nickname' => $gsactor->getNickname()], Router::ABSOLUTE_URL),
        ];
        return Type::create(type: 'Person', attributes: $attr);
    }
}
