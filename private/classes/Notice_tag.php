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

/*
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class Notice_tag extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'notice_tag';                      // table name
    public $tag;                             // varchar(64)  primary_key not_null
    public $notice_id;                       // int(4)  primary_key not_null
    public $created;                         // datetime()

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'description' => 'Hash tags',
            'fields' => array(
                'tag' => array('type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'hash tag associated with this notice'),
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'notice tagged'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
            ),
            'primary key' => array('tag', 'notice_id'),
            'foreign keys' => array(
                'notice_tag_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
            ),
            'indexes' => array(
                'notice_tag_created_idx' => array('created'),
                'notice_tag_notice_id_idx' => array('notice_id'),
                'notice_tag_tag_created_notice_id_idx' => array('tag', 'created', 'notice_id')
            ),
        );
    }

    public static function getStream(
        $tag,
        $offset  = 0,
        $limit   = 20,
        $sinceId = 0,
        $maxId   = 0
    ) {
        // FIXME: Get the Profile::current value some other way
        // to avoid confusino between queue processing and session.
        $stream = new TagNoticeStream($tag, Profile::current());
        return $stream;
    }

    public function blowCache($blowLast = false)
    {
        self::blow('notice_tag:notice_ids:%s', Cache::keyize($this->tag));
        if ($blowLast) {
            self::blow('notice_tag:notice_ids:%s;last', Cache::keyize($this->tag));
        }
    }

    public static function url($tag)
    {
        if (common_config('singleuser', 'enabled')) {
            // Regular TagAction isn't set up in 1user mode
            $nickname = User::singleUserNickname();
            $url = common_local_url(
                'showstream',
                [
                    'nickname' => $nickname,
                    'tag'      => $tag,
                ]
            );
        } else {
            $url = common_local_url('tag', ['tag' => $tag]);
        }

        return $url;
    }
}
