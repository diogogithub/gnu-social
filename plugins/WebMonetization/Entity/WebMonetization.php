<?php

declare(strict_types = 1);

namespace Plugin\WebMonetization\Entity;

use App\Core\Entity;

class WebMonetization extends Entity
{
    // These tags are meant to be literally included and will be populated with the appropriate fields, setters and getters by `bin/generate_entity_fields`
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $sender;
    private int $receiver;
    private float $sent;
    private bool $active;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setSender(int $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    public function getSender(): int
    {
        return $this->sender;
    }

    public function setReceiver(int $receiver): self
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function getReceiver(): int
    {
        return $this->receiver;
    }

    public function setSent(float $sent): self
    {
        $this->sent = $sent;
        return $this;
    }

    public function getSent(): float
    {
        return $this->sent;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode
    public function getNotificationTargetIds(array $ids_already_known = [], ?int $sender_id = null, bool $include_additional = true): array
    {
        if (\array_key_exists('object', $ids_already_known)) {
            $target_ids = $ids_already_known['object'];
        } else {
            $target_ids = [$this->getReceiver()];
        }
        // Additional actors that should know about this
        if ($include_additional && \array_key_exists('additional', $ids_already_known)) {
            array_push($target_ids, ...$ids_already_known['additional']);
            return array_unique($target_ids);
        }
        return $target_ids;
    }
    public static function schemaDef()
    {
        return [
            'name'   => 'webmonetization',
            'fields' => [
                'id'       => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'sender'   => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'not null' => true, 'description' => 'actor sending money'],
                'receiver' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'not null' => true, 'description' => 'actor receiving money'],
                'sent'     => ['type' => 'float', 'not null' => true, 'description' => 'how much sender has sent to receiver'],
                'active'   => ['type' => 'bool', 'not null' => true, 'description' => 'whether it should donate'],
            ],
            'primary key' => ['id'],
        ];
    }
}
