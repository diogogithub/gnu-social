<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Response;

use App\Entity\Actor;
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
        $gsactor->getLocalUser(); // This throws exception if not a local user, which is intended
        return new TypeResponse(data: GSActorToType::translate($gsactor), status: $status);
    }
}
