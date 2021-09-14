<?php

namespace Plugin\ActivityStreamsTwo\Util\Response;

use App\Entity\GSActor;
use Exception;
use Plugin\ActivityStreamsTwo\Util\Model\EntityToType\GSActorToType;

abstract class ActorResponse
{
    /**
     * @param GSActor $gsactor
     * @param int     $status  The response status code
     *
     * @throws Exception
     *
     * @return TypeResponse
     */
    public static function handle(GSActor $gsactor, int $status = 200): TypeResponse
    {
        $gsactor->getLocalUser(); // This throws exception if not a local user, which is intended
        return new TypeResponse(data: GSActorToType::translate($gsactor), status: $status);
    }
}