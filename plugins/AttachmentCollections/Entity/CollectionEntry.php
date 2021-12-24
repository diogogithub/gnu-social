<?php
namespace Plugin\AttachmentCollections\Entity;

use App\Core\Entity;
class CollectionEntry extends Entity
{
    // These tags are meant to be literally included and will be populated with the appropriate fields, setters and getters by `bin/generate_entity_fields`
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
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
            'name'   => 'attachment_album_entry',
            'fields' => [
                'id'            => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'attachment_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to attachment table'],
                'collection_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'AttachmentCollection.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to attachment_collection table'],
            ],
            'primary key' => ['id'],
        ];
    }
}

