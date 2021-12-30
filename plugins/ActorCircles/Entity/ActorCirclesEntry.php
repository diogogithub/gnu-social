<?php

declare(strict_types = 1);

namespace Plugin\ActorCircles\Entity;

use App\Core\Entity;

class ActorCirclesEntry extends Entity
{
    // These tags are meant to be literally included and will be populated with the appropriate fields, setters and getters by `bin/generate_entity_fields`
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $actor_id;
    private int $circle_id;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setCircleId(int $circle_id): self
    {
        $this->circle_id = $circle_id;
        return $this;
    }

    public function getCircleId(): int
    {
        return $this->circle_id;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef()
    {
        return [
            'name'   => 'actor_circles_entry',
            'fields' => [
                'id'        => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'actor_id'  => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to attachment table'],
                'circle_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'actor_circles_a.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to collection table'],
            ],
            'primary key' => ['id'],
        ];
    }
}
