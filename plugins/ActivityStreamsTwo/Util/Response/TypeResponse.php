<?php

namespace Plugin\ActivityStreamsTwo\Util\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class TypeResponse extends JsonResponse
{
    /**
     * @param Type $data
     * @param int  $status The response status code
     *
     * @return JsonResponse
     */
    public function __construct($data = null, int $status = 202)
    {
        parent::__construct(
            data: !is_null($data) ? $data->toJson() : null,
            status: $status,
            headers: ['content-type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'],
            json: true
        );
    }
}
