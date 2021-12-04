<?php

declare(strict_types=1);

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

/**
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Activity;
use DateTimeInterface;

/**
 * Table Definition for activitypub_activity
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivitypubActivity extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $activity_id;
    private string $activity_uri;
    private string $object_uri;
    private bool $is_local;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setActivityId(int $activity_id): self
    {
        $this->activity_id = $activity_id;
        return $this;
    }

    public function getActivityId(): int
    {
        return $this->activity_id;
    }

    public function getActivityUri(): string
    {
        return $this->activity_uri;
    }

    public function setActivityUri(string $activity_uri): self
    {
        $this->activity_uri = $activity_uri;
        return $this;
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

    public function getActivity(): Activity
    {
        return DB::findOneBy('activity', ['id' => $this->getActivityId()]);
    }

    public static function schemaDef(): array
    {
        return [
            'name' => 'activitypub_activity',
            'fields' => [
                'activity_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Activity.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'activity_id to give attention'],
                'activity_uri' => ['type' => 'text', 'not null' => true, 'description' => 'Activity\'s URI'],
                'object_uri' => ['type' => 'text', 'not null' => true, 'description' => 'Object\'s URI'],
                'is_local' => ['type' => 'bool', 'not null' => true, 'description' => 'whether this was a locally generated or an imported activity'],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['activity_uri'],
            'indexes' => [
                'activity_activity_uri_idx' => ['activity_uri'],
                'activity_object_uri_idx' => ['object_uri'],
            ],
        ];
    }
}
