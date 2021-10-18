<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\AS2ToEntity;

use App\Core\Event;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Formatting;
use Component\FreeNetwork\Entity\FreenetworkActor;
use DateTime;
use Exception;

abstract class AS2ToNote
{
    /**
     *@throws Exception
     */
    public static function translate(array $object, ?string $source = null): Note
    {
        $actor_id = FreenetworkActor::getOrCreateByRemoteUri(actor_uri: $object['attributedTo'])->getActorId();
        $map      = [
            'is_local'     => false,
            'created'      => new DateTime($object['published'] ?? 'now'),
            'content'      => $object['content'] ?? null,
            'content_type' => 'text/html',
            'url'          => \array_key_exists('url', $object) ? $object['url'] : $object['id'],
            'actor_id'     => $actor_id,
            'modified'     => new DateTime(),
            'source'       => $source,
        ];
        if ($map['content'] !== null) {
            Event::handle('RenderNoteContent', [
                $map['content'],
                $map['content_type'],
                &$map['rendered'],
                Actor::getById($actor_id),
                null, // TODO reply to
            ]);
        }

        $obj = new Note();
        foreach ($map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $obj->{$set}($val);
        }
        return $obj;
    }
}
