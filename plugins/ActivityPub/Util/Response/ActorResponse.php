<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Response;

use App\Entity\Actor;
use App\Util\Exception\ClientException;
use Exception;
use Plugin\ActivityPub\Util\Model\EntityToType\GSActorToType;

abstract class ActorResponse
{
    /**
     * @param int $status The response status code
     *
     *@throws Exception
     */
    public static function handle(Actor $gsactor, int $status = 200): TypeResponse
    {
        if ($gsactor->getIsLocal()) {
            return new TypeResponse(data: GSActorToType::translate($gsactor), status: $status);
        } else {
            throw new ClientException('This is a remote actor, you should request it to its source of authority instead.');
        }
    }
}
