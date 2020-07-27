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
 * Table Definition for local_group
 */

defined('GNUSOCIAL') || die();

class Local_group extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'local_group';                     // table name
    public $group_id;                        // int(4)  primary_key not_null
    public $nickname;                        // varchar(64)  unique_key
    public $created;                         // datetime()
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'description' => 'Record for a user group on the local site, with some additional info not in user_group',
            'fields' => array(
                'group_id' => array('type' => 'int', 'not null' => true, 'description' => 'group represented'),
                'nickname' => array('type' => 'varchar', 'length' => 64, 'description' => 'group represented'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('group_id'),
            'foreign keys' => array(
                'local_group_group_id_fkey' => array('user_group', array('group_id' => 'id')),
            ),
            'unique keys' => array(
                'local_group_nickname_key' => array('nickname'),
            ),
        );
    }

    public function getProfile()
    {
        return $this->getGroup()->getProfile();
    }

    public function getGroup()
    {
        $group = new User_group();
        $group->id = $this->group_id;
        $group->find(true);
        if (!$group instanceof User_group) {
            common_log(LOG_ERR, 'User_group does not exist for Local_group: '.$this->group_id);
            throw new NoSuchGroupException(array('id' => $this->group_id));
        }
        return $group;
    }

    public function setNickname($nickname)
    {
        $this->decache();
        $modified = common_sql_now();
        $result = $this->query(sprintf(
            <<<'END'
            UPDATE local_group SET nickname = %1$s, modified = %2$s
              WHERE group_id = %3$d;
            END,
            $this->_quote($nickname),
            $this->_quote($modified),
            $this->group_id
        ));

        if ($result) {
            $this->nickname = $nickname;
            $this->modified = $modified;
            $this->encache();
        } else {
            common_log_db_error($local, 'UPDATE', __FILE__);
            // TRANS: Server exception thrown when updating a local group fails.
            throw new ServerException(_('Could not update local group.'));
        }

        return $result;
    }
}
