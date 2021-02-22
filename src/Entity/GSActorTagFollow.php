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
 * Entity for Gsactor Tag Subscription
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class GSActorTagFollow extends Entity
{
    // {{{ Autocode
    private int $gsactor_tag_id;
    private int $gsactor_id;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setGSActorTagId(int $gsactor_tag_id): self
    {
        $this->gsactor_tag_id = $gsactor_tag_id;
        return $this;
    }

    public function getGSActorTagId(): int
    {
        return $this->gsactor_tag_id;
    }

    public function setGSActorId(int $gsactor_id): self
    {
        $this->gsactor_id = $gsactor_id;
        return $this;
    }

    public function getGSActorId(): int
    {
        return $this->gsactor_id;
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
            'name'   => 'gsactor_tag_follow',
            'fields' => [
                'gsactor_tag' => ['type' => 'int',       'foreign key' => true, 'target' => 'GSActorTag.tag', 'mutiplicity' => 'one to one', 'name' => 'gsactor_tag_follow_gsactor_tag_fkey', 'not null' => true, 'description' => 'foreign key to gsactor_tag'],
                'gsactor_id'  => ['type' => 'int',       'foreign key' => true, 'target' => 'GSActor.id', 'mutiplicity' => 'one to one', 'name' => 'gsactor_tag_follow_gsactor_id_fkey', 'not null' => true, 'description' => 'foreign key to gsactor table'],
                'created'     => ['type' => 'datetime',  'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['gsactor_tag_id', 'gsactor_id'],
            'indexes'     => [
                'gsactor_tag_follow_gsactor_id_idx' => ['gsactor_id'],
                'gsactor_tag_follow_created_idx'    => ['created'],
            ],
        ];
    }
}
