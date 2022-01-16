<?php

declare(strict_types = 1);

namespace Plugin\PinnedNotes\Entity;

use App\Core\Entity;

class PinnedNotes extends Entity
{
    private int $id;
    private int $actor_id;
    private int $note_id;

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getActorId()
    {
        return $this->actor_id;
    }
    public function setActorId($actor_id)
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getNoteId()
    {
        return $this->note_id;
    }
    public function setNoteId($note_id)
    {
        $this->note_id = $note_id;
        return $this;
    }

    public static function schemaDef()
    {
        return [
            'name'   => 'pinned_notes',
            'fields' => [
                'id'       => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'actor_id' => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'description' => 'Actor who pinned the note'],
                'note_id'  => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Note.id',  'multiplicity' => 'many to one', 'description' => 'Pinned note'],
            ],
            'primary key' => ['id'],
        ];
    }
}
