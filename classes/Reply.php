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
 * Table Definition for reply
 */

defined('GNUSOCIAL') || die();

class Reply extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'reply';                           // table name
    public $notice_id;                       // int(4)  primary_key not_null
    public $profile_id;                      // int(4)  primary_key not_null
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP
    public $replied_id;                      // int(4)

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'notice that is the reply'),
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'profile replied to'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
                'replied_id' => array('type' => 'int', 'description' => 'notice replied to (not used, see notice.reply_to)'),
            ),
            'primary key' => array('notice_id', 'profile_id'),
            'foreign keys' => array(
                'reply_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
                'reply_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
            'indexes' => array(
                'reply_notice_id_idx' => array('notice_id'),
                'reply_profile_id_idx' => array('profile_id'),
                'reply_replied_id_idx' => array('replied_id'),
                'reply_profile_id_modified_notice_id_idx' => array('profile_id', 'modified', 'notice_id')
            ),
        );
    }
    
    /**
     * Wrapper for record insertion to update related caches
     */
    public function insert()
    {
        $result = parent::insert();

        if ($result) {
            self::blow('reply:stream:%d', $this->profile_id);
        }

        return $result;
    }

    public static function stream(
        $user_id,
        $offset   = 0,
        $limit    = NOTICES_PER_PAGE,
        $since_id = 0,
        $max_id   = 0
    ) {
        // FIXME: Use some other method to get Profile::current() in order
        // to avoid confusion between background processing and session user.
        $stream = new ReplyNoticeStream($user_id, Profile::current());
        return $stream->getNotices($offset, $limit, $since_id, $max_id);
    }
}
