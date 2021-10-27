<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\AS2ToEntity;

use App\Core\Event;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Formatting;
use DateTime;
use Exception;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Entity\ActivitypubActivity;

abstract class AS2ToNote
{
    /**
     *@throws Exception
     */
    public static function translate(array $object, ?string $source, ?string $actor_uri, ?ActivitypubActivity $act = null): Note
    {
        if (isset($actor_uri) && $actor_uri === $object['attributedTo']) {
            $actor_id = $act->getActorId();
        } else {
            $actor_id = ActivityPub::getActorByUri($object['attributedTo'])->getId();
        }
        $map = [
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
