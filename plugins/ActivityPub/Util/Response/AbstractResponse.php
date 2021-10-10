<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Response;

use Exception;
use Plugin\ActivityPub\Util\Model\EntityToType\EntityToType;

abstract class AbstractResponse
{
    /**
     * param Type $type // What is this `Type`
     *
     * @param int $status The response status code
     *
     * @throws Exception
     */
    public static function handle($type, int $status = 200): TypeResponse
    {
        return new TypeResponse(
            data: EntityToType::translate($type),
            status: $status,
        );
    }
}
