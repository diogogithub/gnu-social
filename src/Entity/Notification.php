<?php

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

namespace App\Entity;

use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for attentions
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Notification extends Entity
{
    // {{{ Autocode

    private int $activity_id;
    private int $gsactor_id;
    private ?string $reason;
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

    public function setGsactorId(int $gsactor_id): self
    {
        $this->gsactor_id = $gsactor_id;
        return $this;
    }

    public function getGsactorId(): int
    {
        return $this->gsactor_id;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
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

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'notification',
            'description' => 'Activity notification for gsactors (that are not a mention and not result of a subscription)',
            'fields'      => [
                'activity_id' => ['type' => 'int', 'not null' => true,  'description' => 'activity_id to give attention'],
                'gsactor_id'  => ['type' => 'int', 'not null' => true,  'description' => 'gsactor_id for feed receiver'],
                'reason'      => ['type' => 'varchar', 'length' => 191, 'description' => 'Optional reason why this was brought to the attention of gsactor_id'],
                'created'     => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['activity_id', 'gsactor_id'],
            'foreign keys' => [
                'attention_activity_id_fkey' => ['activity', ['activity_id' => 'id']],
                'attention_gsactor_id_fkey'  => ['gsactor', ['gsactor_id' => 'id']],
            ],
            'indexes' => [
                'attention_activity_id_idx' => ['activity_id'],
                'attention_gsactor_id_idx'  => ['gsactor_id'],
            ],
        ];
    }
}
