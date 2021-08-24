<?php

namespace Plugin\ActivityStreamsTwo\Util\Model\EntityToType;

use App\Core\Router\Router;
use App\Entity\Note;
use DateTimeInterface;
use Plugin\ActivityStreamsTwo\Util\Type;

class NoteToType
{
    /**
     * @param $entity
     *
     * @throws \Exception
     *
     * @return Type
     */
    public static function translate(Note $note)
    {
        $attr = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => Router::url('note_view', ['id' => $note->getId()], Router::ABSOLUTE_URL),
            'published' => $note->getCreated()->format(DateTimeInterface::RFC3339),
            //'attributedTo' => Router::url('actor', ['id' => $note->getGSActorId()]),
            //'to' => $to,
            //'cc' => $cc,
            'content' => json_encode($note->getContent()), // change to rendered
            //'tag' => $tags
        ];
        return Type::create(type: 'Note', attributes: $attr);
    }
}