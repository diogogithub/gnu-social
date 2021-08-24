<?php

namespace Plugin\ActivityStreamsTwo\Util\Response;

use Plugin\ActivityStreamsTwo\Util\Model\EntityToType\EntityToType;

abstract class AbstractResponse
{
    /**
     * @param Type $type
     * @param int  $status The response status code
     *
     * @throws \Exception
     *
     * @return TypeResponse
     */
    public static function handle($type, int $status = 200): TypeResponse
    {
        return new TypeResponse(
            data: EntityToType::translate($type),
            status: $status
        );
    }
}