<?php

declare(strict_types=1);

namespace Plugin\ActivityPub\Util\Model\EntityToType;

use App\Core\Router\Router;
use App\Entity\Note;
use DateTimeInterface;
use Exception;
use Plugin\ActivityPub\Util\Type;

class NoteToType
{
    /**
     * @throws Exception
     */
    public static function translate(Note $note): Type\Extended\Object\Note
    {
        $attributedTo = null;
        $attr = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => Router::url('note_view', ['id' => $note->getId()], Router::ABSOLUTE_URL),
            'published' => $note->getCreated()->format(DateTimeInterface::RFC3339),
            'attributedTo' => $attributedTo,
            //'to' => $to,
            //'cc' => $cc,
            'content' => json_encode($note->getRendered()),
            //'tag' => $tags
        ];
        return Type::create(type: 'Note', attributes: $attr);
    }
}
