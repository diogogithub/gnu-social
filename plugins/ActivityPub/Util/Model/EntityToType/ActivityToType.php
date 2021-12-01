<?php

declare(strict_types=1);

namespace Plugin\ActivityPub\Util\Model\EntityToType;

use App\Core\Router\Router;
use App\Entity\Activity;
use App\Util\Exception\ClientException;
use DateTimeInterface;
use Exception;
use Plugin\ActivityPub\Util\Type;

class ActivityToType
{
    public static function gs_verb_to_activity_stream_two_verb($verb)
    {
        return match ($verb) {
            'create'  => 'Create',
            default => throw new ClientException('Invalid verb'),
        };
    }

    /**
     * @throws Exception
     */
    public static function translate(Activity $activity): Type\Core\Activity
    {
        $attr = [
            'type' => self::gs_verb_to_activity_stream_two_verb($activity->getVerb()),
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => Router::url('activity_view', ['id' => $activity->getId()], Router::ABSOLUTE_URL),
            'published' => $activity->getCreated()->format(DateTimeInterface::RFC3339),
            'actor' => $activity->getActor()->getUri(Router::ABSOLUTE_URL),
            'to' => ['https://www.w3.org/ns/activitystreams#Public'], // TODO: implement proper scope address
            'cc' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => EntityToType::translate($activity->getObject()),
        ];
        return Type::create($attr);
    }
}
