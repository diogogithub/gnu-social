<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for user IM preferences
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2009 StatusNet Inc.
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class UserImPrefs
{
    // {{{ Autocode

    private int $user_id;
    private string $screenname;
    private string $transport;
    private bool $notify;
    private bool $replies;
    private bool $updatefrompresence;
    private DateTime $created;
    private DateTime $modified;

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }
    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setScreenname(string $screenname): self
    {
        $this->screenname = $screenname;
        return $this;
    }
    public function getScreenname(): string
    {
        return $this->screenname;
    }

    public function setTransport(string $transport): self
    {
        $this->transport = $transport;
        return $this;
    }
    public function getTransport(): string
    {
        return $this->transport;
    }

    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;
        return $this;
    }
    public function getNotify(): bool
    {
        return $this->notify;
    }

    public function setReplies(bool $replies): self
    {
        $this->replies = $replies;
        return $this;
    }
    public function getReplies(): bool
    {
        return $this->replies;
    }

    public function setUpdatefrompresence(bool $updatefrompresence): self
    {
        $this->updatefrompresence = $updatefrompresence;
        return $this;
    }
    public function getUpdatefrompresence(): bool
    {
        return $this->updatefrompresence;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setModified(DateTime $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'user_im_prefs',
            'fields' => [
                'user_id'            => ['type' => 'int', 'not null' => true, 'description' => 'user'],
                'screenname'         => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'screenname on this service'],
                'transport'          => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'transport (ex xmpp, aim)'],
                'notify'             => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify when a new notice is sent'],
                'replies'            => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Send replies from people not subscribed to'],
                'updatefrompresence' => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Update from presence.'],
                'created'            => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'           => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['user_id', 'transport'],
            'unique keys' => [
                'transport_screenname_key' => ['transport', 'screenname'],
            ],
            'foreign keys' => [
                'user_im_prefs_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
        ];
    }
}