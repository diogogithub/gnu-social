<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\AS2ToEntity;

use App\Core\DB\DB;
use App\Core\Event;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Exception\ClientException;
use App\Util\Formatting;
use DateTime;
use Plugin\ActivityPub\ActivityPub;
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
    public static function store(array $activity, ?string $source = null): ActivitypubActivity
    {
        $ap_act = ActivitypubActivity::getWithPK(['activity_uri' => $activity['id']]);
        if (\is_null($ap_act)) {
            $actor = ActivityPub::getActorByUri($activity['actor']);
            // Store Object
            $obj = null;
            switch ($activity['object']['type']) {
                case 'Note':
                    $obj = AS2ToNote::translate($activity['object'], $source, $activity['actor'], $actor->getId());
                    break;
                default:
                    if (!Event::handle('ActivityPubObject', [$activity['object']['type'], $activity['object'], &$obj])) {
                        throw new ClientException('Unsupported Object type.');
                    }
                    break;
            }
            DB::persist($obj);
            // Store Activity
            $act = Activity::create([
                'actor_id'     => $actor->getId(),
                'verb'         => self::activity_stream_two_verb_to_gs_verb($activity['type']),
                'object_type'  => self::activity_stream_two_object_type_to_gs_table($activity['object']['type']),
                'object_id'    => $obj->getId(),
                'is_local'     => false,
                'created'      => new DateTime($activity['published'] ?? 'now'),
                'source'       => $source,
            ]);
            DB::persist($act);
            // Store ActivityPub Activity
            $ap_act = ActivitypubActivity::create([
                'activity_id'  => $act->getId(),
                'activity_uri' => $activity['id'],
                'object_uri'   => $activity['object']['id'],
                'is_local'     => false,
                'created'      => new DateTime($activity['published'] ?? 'now'),
                'modified'     => new DateTime(),
            ]);
            DB::persist($ap_act);
        }

        return $ap_act;
    }
}
