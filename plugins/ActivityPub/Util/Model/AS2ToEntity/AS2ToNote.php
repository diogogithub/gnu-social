<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\AS2ToEntity;

use App\Core\Event;
use App\Entity\Actor;
use App\Entity\Language;
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
    public static function translate(array $object, ?string $source, ?string $actor_uri = null, ?int $actor_id = null): Note
    {
        if (is_null($actor_uri) || $actor_uri !== $object['attributedTo']) {
            $actor_id = ActivityPub::getActorByUri($object['attributedTo'])->getId();
        }
        $map = [
            'is_local'     => false,
            'created'      => new DateTime($object['published'] ?? 'now'),
            'content'      => $object['content'] ?? null,
            'content_type' => 'text/html',
            'language_id'  => $object['contentLang'] ?? null,
            'url'          => \array_key_exists('url', $object) ? $object['url'] : $object['id'],
            'actor_id'     => $actor_id,
            'modified'     => new DateTime(),
            'source'       => $source,
        ];
        if ($map['content'] !== null) {
            $mentions = [];
            Event::handle('RenderNoteContent', [
                $map['content'],
                $map['content_type'],
                &$map['rendered'],
                Actor::getById($actor_id),
                $map['language_id'],
                &$mentions,
            ]);
        }

        $obj = new Note();

        if (!is_null($map['language_id'])) {
            $map['language_id'] = Language::getFromLocale($map['language_id'])->getId();
        } else {
            $map['language_id'] = null;
        }

        foreach ($map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $obj->{$set}($val);
        }

        Event::handle('NewNoteFromActivityStreamsTwo', [$source, $obj, $actor_id]);

        return $obj;
    }
}
