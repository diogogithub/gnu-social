<?php

namespace Plugin\ActivityPub\Util\Model\EntityToType;

use App\Core\Event;
use App\Core\Router\Router;
use App\Entity\Note;
use DateTimeInterface;
use Plugin\ActivityPub\Util\Type;

class NoteToType
{
    /**
     * @param Note $note
     *
     * @throws \Exception
     *
     * @return Type
     */
    public static function translate(Note $note)
    {
        $attributedTo = null;
        Event::handle('FreeNetworkGenerateLocalActorUri', ['source' => 'ActivityPub', 'actor_id' => $note->getActorId(), 'actor_uri' => &$attributedTo]);
        $attr = [
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            'id'           => Router::url('note_view', ['id' => $note->getId()], Router::ABSOLUTE_URL),
            'published'    => $note->getCreated()->format(DateTimeInterface::RFC3339),
            'attributedTo' => $attributedTo,
            //'to' => $to,
            //'cc' => $cc,
            'content' => json_encode($note->getRendered()),
            //'tag' => $tags
        ];
        return Type::create(type: 'Note', attributes: $attr);
    }
}
