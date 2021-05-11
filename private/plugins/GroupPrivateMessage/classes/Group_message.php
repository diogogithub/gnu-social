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
 * Data class for group direct messages
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

/**
 * Data class for group direct messages
 *
 * @category  GroupPrivateMessage
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Group_message extends Managed_DataObject
{
    public $__table = 'group_message'; // table name
    public $id;                        // char(36)  primary_key not_null
    public $uri;                       // varchar(191)   not 255 because utf8mb4 takes more space
    public $from_profile;              // int
    public $to_group;                  // int
    public $content;
    public $rendered;
    public $url;                       // varchar(191)   not 255 because utf8mb4 takes more space
    public $created;                   // datetime()   not_null
    public $modified;                  // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'char', 'not null' => true, 'length' => 36, 'description' => 'message uuid'),
                'uri' => array('type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'message uri'),
                'url' => array('type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'representation url'),
                'from_profile' => array('type' => 'int', 'not null' => true, 'description' => 'sending profile ID'),
                'to_group' => array('type' => 'int', 'not null' => true, 'description' => 'receiving group ID'),
                'content' => array('type' => 'text', 'not null' => true, 'description' => 'message content'),
                'rendered' => array('type' => 'text', 'not null' => true, 'description' => 'rendered message'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('id'),
            'unique keys' => array(
                'group_message_uri_key' => array('uri'),
            ),
            'foreign keys' => array(
                'group_message_from_profile_fkey' => array('profile', array('from_profile' => 'id')),
                'group_message_to_group_fkey' => array('user_group', array('to_group' => 'id')),
            ),
            'indexes' => array(
                'group_message_from_profile_idx' => array('from_profile'),
                'group_message_to_group_created_idx' => array('to_group', 'created'),
                'group_message_url_idx' => array('url'),
            ),
        );
    }

    public static function send($user, $group, $text)
    {
        if (!$user->hasRight(Right::NEWMESSAGE)) {
            // XXX: maybe break this out into a separate right
            // TRANS: Exception thrown when trying to send group private message without having the right to do that.
            // TRANS: %s is a user nickname.
            throw new Exception(sprintf(
                _m('User %s is not allowed to send private messages.'),
                $user->nickname
            ));
        }

        Group_privacy_settings::ensurePost($user, $group);

        $text = $user->shortenLinks($text);

        // We use the same limits as for 'regular' private messages.

        if (Message::contentTooLong($text)) {
            // TRANS: Exception thrown when trying to send group private message that is too long.
            // TRANS: %d is the maximum meggage length.
            throw new Exception(sprintf(
                _m(
                    'That\'s too long. Maximum message size is %d character.',
                    'That\'s too long. Maximum message size is %d characters.',
                    Message::maxContent()
                ),
                Message::maxContent()
            ));
        }

        // Valid! Let's do this thing!

        $gm = new Group_message();

        $gm->id           = UUID::gen();
        $gm->uri          = common_local_url('showgroupmessage', array('id' => $gm->id));
        $gm->from_profile = $user->id;
        $gm->to_group     = $group->id;
        $gm->content      = $text; // XXX: is this cool?!
        $gm->rendered     = common_render_text($text);
        $gm->url          = $gm->uri;
        $gm->created      = common_sql_now();

        // This throws a conniption if there's a problem

        $gm->insert();

        $gm->distribute();

        return $gm;
    }

    public function distribute()
    {
        $group = User_group::getKV('id', $this->to_group);

        $member = $group->getMembers();

        while ($member->fetch()) {
            Group_message_profile::send($this, $member);
        }
    }

    public function getGroup()
    {
        $group = User_group::getKV('id', $this->to_group);
        if (empty($group)) {
            // TRANS: Exception thrown when trying to send group private message to a non-existing group.
            throw new ServerException(_m('No group for group message.'));
        }
        return $group;
    }

    public function getSender()
    {
        $sender = Profile::getKV('id', $this->from_profile);
        if (empty($sender)) {
            // TRANS: Exception thrown when trying to send group private message without having a sender.
            throw new ServerException(_m('No sender for group message.'));
        }
        return $sender;
    }

    public static function forGroup($group, $offset, $limit)
    {
        // XXX: cache
        $gm = new Group_message();

        $gm->to_group = $group->id;
        $gm->orderBy('created DESC');
        $gm->limit($offset, $limit);

        $gm->find();

        return $gm;
    }
}
