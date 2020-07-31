<?php
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

/**
 * A channel for real-time browser data
 *
 * For each user currently browsing the site, we want to know which page they're on
 * so we can send real-time updates to their browser.
 *
 * @category  Realtime
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * A channel for real-time browser data
 *
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see       DB_DataObject
 */
class Realtime_channel extends Managed_DataObject
{
    const TIMEOUT = 1800; // 30 minutes

    public $__table = 'realtime_channel'; // table name

    public $user_id;       // int -> user.id, can be null
    public $action;        // varchar(191)                  not 255 because utf8mb4 takes more space
    public $arg1;          // varchar(191)   argument       not 255 because utf8mb4 takes more space
    public $arg2;          // varchar(191)   usually null   not 255 because utf8mb4 takes more space
    public $channel_key;   // 128-bit shared secret key
    public $audience;      // listener count
    public $created;       // created date
    public $modified;      // modified date

    /**
     * The One True Thingy that must be defined and declared.
     */
    public static function schemaDef()
    {
        return [
            'description' => 'A channel of realtime notice data',
            'fields' => [
                'user_id' => ['type' => 'int',
                    'not null' => false,
                    'description' => 'user viewing page; can be null'],
                'action' => ['type' => 'varchar',
                    'length' => 191,
                    'not null' => true,
                    'description' => 'page being viewed'],
                'arg1' => ['type' => 'varchar',
                    'length' => 191,
                    'not null' => false,
                    'description' => 'page argument, like username or tag'],
                'arg2' => ['type' => 'varchar',
                    'length' => 191,
                    'not null' => false,
                    'description' => 'second page argument, like tag for showstream'],
                'channel_key' => ['type' => 'varchar',
                    'length' => 32,
                    'not null' => true,
                    'description' => 'shared secret key for this channel'],
                'audience' => ['type' => 'int',
                    'not null' => true,
                    'default' => 0,
                    'description' => 'reference count'],
                'created' => ['type' => 'datetime',
                    'not null' => true,
                    'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime',
                    'not null' => true,
                    'description' => 'date this record was modified'],
            ],
            'primary key' => ['channel_key'],
            'unique keys' => [
                'realtime_channel_user_id_action_arg1_arg2_key' => ['user_id', 'action', 'arg1', 'arg2'],
            ],
            'foreign keys' => [
                'realtime_channel_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
            'indexes' => [
                'realtime_channel_modified_idx' => ['modified'],
                'realtime_channel_page_idx' => ['action', 'arg1', 'arg2']
            ],
        ];
    }

    public static function saveNew(int $user_id, Action $action, $arg1, $arg2): Realtime_channel
    {
        $channel = new Realtime_channel();

        $channel->user_id = $user_id;
        $channel->action = $action;
        $channel->arg1 = $arg1;
        $channel->arg2 = $arg2;
        $channel->audience = 1;

        $channel->channel_key = common_random_hexstr(16); // 128-bit key, 32 hex chars

        $channel->created = common_sql_now();
        $channel->modified = $channel->created;

        $channel->insert();

        return $channel;
    }

    public static function getChannel(int $user_id, Action $action, $arg1, $arg2): Realtime_channel
    {
        $channel = self::fetchChannel($user_id, $action, $arg1, $arg2);

        // Ignore (and delete!) old channels

        if (!empty($channel)) {
            $modTime = strtotime($channel->modified);
            if ((time() - $modTime) > self::TIMEOUT) {
                $channel->delete();
                $channel = null;
            }
        }

        if (empty($channel)) {
            $channel = self::saveNew($user_id, $action, $arg1, $arg2);
        }

        return $channel;
    }

    public static function getAllChannels(Action $action, $arg1, $arg2): array
    {
        $channel = new Realtime_channel();

        $channel->action = $action;

        if (is_null($arg1)) {
            $channel->whereAdd('arg1 is null');
        } else {
            $channel->arg1 = $arg1;
        }

        if (is_null($arg2)) {
            $channel->whereAdd('arg2 is null');
        } else {
            $channel->arg2 = $arg2;
        }

        $channel->whereAdd(sprintf("modified > TIMESTAMP '%s'", common_sql_date(time() - self::TIMEOUT)));

        $channels = [];

        if ($channel->find()) {
            $channels = $channel->fetchAll();
        }

        return $channels;
    }

    public static function fetchChannel(int $user_id, Action $action, $arg1, $arg2): ?Realtime_channel
    {
        $channel = new Realtime_channel();

        if (is_null($user_id)) {
            $channel->whereAdd('user_id is null');
        } else {
            $channel->user_id = $user_id;
        }

        $channel->action = $action;

        if (is_null($arg1)) {
            $channel->whereAdd('arg1 is null');
        } else {
            $channel->arg1 = $arg1;
        }

        if (is_null($arg2)) {
            $channel->whereAdd('arg2 is null');
        } else {
            $channel->arg2 = $arg2;
        }

        if ($channel->find(true)) {
            $channel->increment();
            return $channel;
        } else {
            return null;
        }
    }

    public function increment(): void
    {
        // XXX: race
        $orig = clone($this);
        $this->audience++;
        $this->modified = common_sql_now();
        $this->update($orig);
    }

    public function touch(): void
    {
        // XXX: race
        $orig = clone($this);
        $this->modified = common_sql_now();
        $this->update($orig);
    }

    public function decrement(): void
    {
        // XXX: race
        if ($this->audience == 1) {
            $this->delete();
        } else {
            $orig = clone($this);
            $this->audience--;
            $this->modified = common_sql_now();
            $this->update($orig);
        }
    }
}
