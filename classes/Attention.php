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
 * Data class for Attentions
 *
 * @category  Data
 * @package   GNUsocial
 * @copyright 2014 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class Attention extends Managed_DataObject
{
    public $__table = 'attention';  // table name
    public $notice_id;              // int(4) primary_key not_null
    public $profile_id;             // int(4) primary_key not_null
    public $reason;                 // varchar(191)
    public $created;                // datetime()
    public $modified;               // timestamp()  not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'description' => 'Notice attentions to profiles (that are not a mention and not result of a subscription)',
            'fields' => array(
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'notice_id to give attention'),
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'profile_id for feed receiver'),
                'reason' => array('type' => 'varchar', 'length' => 191, 'description' => 'Optional reason why this was brought to the attention of profile_id'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('notice_id', 'profile_id'),
            'foreign keys' => array(
                'attention_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
                'attention_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
            'indexes' => array(
                'attention_notice_id_idx' => array('notice_id'),
                'attention_profile_id_idx' => array('profile_id'),
            ),
        );
    }

    public static function saveNew(Notice $notice, Profile $target, $reason=null)
    {
        try {
            $att = Attention::getByKeys(['notice_id'=>$notice->getID(), 'profile_id'=>$target->getID()]);
            throw new AlreadyFulfilledException('Attention already exists with reason: '._ve($att->reason));
        } catch (NoResultException $e) {
            $att = new Attention();
        
            $att->notice_id = $notice->getID();
            $att->profile_id = $target->getID();
            $att->reason = $reason;
            $att->created = common_sql_now();
            $result = $att->insert();

            if ($result === false) {
                throw new Exception('Failed Attention::saveNew for notice id=='.$notice->getID().' target id=='.$target->getID().', reason=="'.$reason.'"');
            }
        }
        self::blow('attention:stream:%d', $target->getID());
        return $att;
    }
}
