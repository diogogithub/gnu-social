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

use DateTimeInterface;

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
class UserNotificationPrefs
{
    // {{{ Autocode

    private int $user_id;
    private string $screenname;
    private string $transport;
    private bool $notify;
    private bool $replies;
    private bool $updatefrompresence;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

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
            'name'   => 'user_notification_prefs',
            'fields' => [
                'user_id'               => ['type' => 'int', 'not null' => true],
                'service_name'          => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'name on this service'],
                'transport'             => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'transport (ex xmpp, aim)'],
                'profile_id'            => ['type' => 'int',  'default' => null, 'description' => 'If not null, settings are specific only to a given profiles'],
                'posts_by_followed'     => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify when a new notice by someone we follow is made'],
                'mention'               => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify when mentioned by someone we do not follow'],
                'follow'                => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify someone follows us'],
                'favorite'              => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify someone favorites a notice by us'],
                'nudge'                 => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify someone nudges us'],
                'dm'                    => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify someone sends us a direct message'],
                'post_on_status_change' => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Post a notice when our status in service changes'],
                'enable_posting'        => ['type' => 'bool', 'default' => true,  'description' => 'Enable posting from this service'],
                'created'               => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'              => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['user_id', 'transport'],
            'unique keys' => [
                'transport_service_key' => ['transport', 'service_name'],
            ],
            'foreign keys' => [
                'user_notification_prefs_user_id_fkey' => ['user', ['user_id' => 'id']],
                'user_notification_prefs_profile'      => ['profile', ['profile_id' => 'id']],
            ],
            'indexes' => [
                'user_notification_prefs_user_profile_idx' => ['user_id', 'profile_id'],
            ],
        ];
    }
}
