<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\AS2ToEntity;

use App\Core\DB\DB;
use App\Core\Event;
use App\Util\Exception\ClientException;
use App\Util\Formatting;
use Component\FreeNetwork\Entity\FreenetworkActor;
use DateTime;
use Plugin\ActivityPub\Entity\ActivitypubActivity;

abstract class AS2ToEntity
{
    public static function activity_stream_two_verb_to_gs_verb($verb)
    {
        return match ($verb) {
            'Create' => 'create',
            default  => throw new ClientException('Invalid verb'),
        };
    }

    public static function activity_stream_two_object_type_to_gs_table($verb)
    {
        return match ($verb) {
            'Note'  => 'note',
            default => throw new ClientException('Invalid verb'),
        };
    }

    /**
     * @throws ClientException
     */
    public static function store(array $activity, ?string $source = null): array
    {
        $map = [
            'activity_uri' => $activity['id'],
            'actor_id'     => FreenetworkActor::getOrCreateByUri(actor_uri: $activity['actor'], source: 'ActivityPub')->getActorId(),
            'verb'         => self::activity_stream_two_verb_to_gs_verb($activity['type']),
            'object_type'  => self::activity_stream_two_object_type_to_gs_table($activity['object']['type']),
            'object_uri'   => $activity['object']['id'],
            'is_local'     => false,
            'created'      => new DateTime($activity['published'] ?? 'now'),
            'modified'     => new DateTime(),
            'source'       => $source,
        ];

        $act = new ActivitypubActivity();
        foreach ($map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $act->{$set}($val);
        }

        $obj = null;
        switch ($activity['object']['type']) {
            case 'Note':
                $obj = AS2ToNote::translate($activity['object'], $source);
                break;
            default:
                if (!Event::handle('ActivityPubObject', [$activity['object']['type'], $activity['object'], &$obj])) {
                    throw new ClientException('Unsupported Object type.');
                }
                break;
        }

        DB::persist($obj);
        $act->setObjectId($obj->getId());
        DB::persist($act);

        return [$act, $obj];
    }
}
