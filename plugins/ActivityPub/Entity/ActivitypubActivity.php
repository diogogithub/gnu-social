<?php

declare(strict_types = 1);

// {{{ License

// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

// }}}

namespace Plugin\ActivityPub\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for all activities we know about
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivitypubActivity extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $activity_uri;
    private int $actor_id;
    private string $verb;
    private string $object_type;
    private int $object_id;
    private string $object_uri;
    private bool $is_local;
    private ?string $source;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function getActivityUri(): string
    {
        return $this->activity_uri;
    }

    public function setActivityUri(string $activity_uri): self
    {
        $this->activity_uri = $activity_uri;
        return $this;
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

    public function setVerb(string $verb): self
    {
        $this->verb = $verb;
        return $this;
    }

    public function getVerb(): string
    {
        return $this->verb;
    }

    public function setObjectType(string $object_type): self
    {
        $this->object_type = $object_type;
        return $this;
    }

    public function getObjectType(): string
    {
        return $this->object_type;
    }

    public function setObjectId(int $object_id): self
    {
        $this->object_id = $object_id;
        return $this;
    }

    public function getObjectId(): int
    {
        return $this->object_id;
    }

    public function getObjectUri(): string
    {
        return $this->object_uri;
    }

    public function setObjectUri(string $object_uri): self
    {
        $this->object_uri = $object_uri;
        return $this;
    }

    public function setIsLocal(bool $is_local): self
    {
        $this->is_local = $is_local;
        return $this;
    }

    public function getIsLocal(): bool
    {
        return $this->is_local;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'activitypub_activity',
            'fields' => [
                'activity_uri' => ['type' => 'text',     'not null' => true, 'description' => 'Activity\'s URI'],
                'actor_id'     => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'who made the note'],
                'verb'         => ['type' => 'varchar',  'length' => 32,     'not null' => true, 'description' => 'internal activity verb, influenced by activity pub verbs'],
                'object_type'  => ['type' => 'varchar',  'length' => 32, 'description' => 'the name of the table this object refers to'],
                'object_id'    => ['type' => 'int',   'description' => 'id in the referenced table'],
                'object_uri'   => ['type' => 'text',     'not null' => true, 'description' => 'Object\'s URI'],
                'is_local'     => ['type' => 'bool',     'not null' => true, 'description' => 'whether this was a locally generated or an imported activity'],
                'source'       => ['type' => 'varchar',  'length' => 32,     'description' => 'the source of this activity'],
                'created'      => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['activity_uri'],
            'indexes'     => [
                'activity_activity_uri_idx' => ['activity_uri'],
                'activity_object_uri_idx'   => ['object_uri'],
            ],
        ];
    }
}
