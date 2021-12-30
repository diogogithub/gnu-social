<?php

declare(strict_types = 1);

namespace Plugin\ActorCircles\Entity;

use App\Core\Entity;

class ActorCircles extends Entity
{
    // These tags are meant to be literally included and will be populated with the appropriate fields, setters and getters by `bin/generate_entity_fields`
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private string $name;
    private int $actor_id;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = mb_substr($name, 0, 255);
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode
    public static function schemaDef()
    {
        return [
            'name'   => 'actor_circles_a',
            'fields' => [
                'id'       => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'name'     => ['type' => 'varchar', 'length' => 255, 'not null' => true, 'description' => 'collection\'s name'],
                'actor_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to many', 'not null' => true, 'description' => 'foreign key to actor table'],
            ],
            'primary key' => ['id'],
        ];
    }
}
