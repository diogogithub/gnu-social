<?php

namespace Plugin\ActivityStreamsTwo\Util\Response;

use App\Entity\Actor;
use Exception;
use Plugin\ActivityStreamsTwo\Util\Model\EntityToType\GSActorToType;

abstract class ActorResponse
{
    /**
     * @param Actor $gsactor
     * @param int   $status  The response status code
     *
     *@throws Exception
     *
     * @return TypeResponse
     *
     */
    public static function handle(Actor $gsactor, int $status = 200): TypeResponse
    {
        $gsactor->getLocalUser(); // This throws exception if not a local user, which is intended
        return new TypeResponse(data: GSActorToType::translate($gsactor), status: $status);
    }
}