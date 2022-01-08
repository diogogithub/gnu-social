<?php

declare(strict_types = 1);

namespace Plugin\WebMonetization\Entity;

use App\Core\Entity;

class Wallet extends Entity
{
    // These tags are meant to be literally included and will be populated with the appropriate fields, setters and getters by `bin/generate_entity_fields`
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $actor_id;
    private string $address;

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

    public function setAddress(string $address): self
    {
        $this->address = mb_substr($address, 0, 255);
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef()
    {
        return [
            'name'   => 'webmonetizationWallet',
            'fields' => [
                'id'       => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'actor_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to actor table'],
                'address'  => ['type' => 'varchar', 'length' => 255, 'not null' => true, 'description' => 'wallet address'],
            ],
            'primary key' => ['id'],
        ];
    }
}
