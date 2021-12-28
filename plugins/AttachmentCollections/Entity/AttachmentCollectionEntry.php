<?php

declare(strict_types = 1);

namespace Plugin\AttachmentCollections\Entity;

use App\Core\Entity;

class AttachmentCollectionEntry extends Entity
{
    // These tags are meant to be literally included and will be populated with the appropriate fields, setters and getters by `bin/generate_entity_fields`
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $note_id;
    private int $attachment_id;
    private int $collection_id;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setNoteId(int $note_id): self
    {
        $this->note_id = $note_id;
        return $this;
    }

    public function getNoteId(): int
    {
        return $this->note_id;
    }

    public function setAttachmentId(int $attachment_id): self
    {
        $this->attachment_id = $attachment_id;
        return $this;
    }

    public function getAttachmentId(): int
    {
        return $this->attachment_id;
    }

    public function setCollectionId(int $collection_id): self
    {
        $this->collection_id = $collection_id;
        return $this;
    }

    public function getCollectionId(): int
    {
        return $this->collection_id;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef()
    {
        return [
            'name'   => 'attachment_collection_entry',
            'fields' => [
                'id'            => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'note_id'       => ['type' => 'int', 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to note table'],
                'attachment_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to attachment table'],
                'collection_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Collection.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to collection table'],
            ],
            'primary key' => ['id'],
        ];
    }
}
