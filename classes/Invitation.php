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
 * Table Definition for invitation
 */

defined('GNUSOCIAL') || die();

class Invitation extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'invitation';                      // table name
    public $code;                            // varchar(32)  primary_key not_null
    public $user_id;                         // int(4)   not_null
    public $address;                         // varchar(191)  multiple_key not_null   not 255 because utf8mb4 takes more space
    public $address_type;                    // varchar(8)  multiple_key not_null
    public $registered_user_id;              // int(4)   not_null
    public $created;                         // datetime()

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public function convert($user)
    {
        $orig = clone($this);
        $this->registered_user_id = $user->id;
        return $this->update($orig);
    }

    public static function schemaDef()
    {
        return array(

            'fields' => array(
                'code' => array('type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'random code for an invitation'),
                'user_id' => array('type' => 'int', 'not null' => true, 'description' => 'who sent the invitation'),
                'address' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'invitation sent to'),
                'address_type' => array('type' => 'varchar', 'length' => 8, 'not null' => true, 'description' => 'address type ("email", "xmpp", "sms")'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'registered_user_id' => array('type' => 'int', 'not null' => false, 'description' => 'if the invitation is converted, who the new user is'),
            ),
            'primary key' => array('code'),
            'foreign keys' => array(
                'invitation_user_id_fkey' => array('user', array('user_id' => 'id')),
                'invitation_registered_user_id_fkey' => array('user', array('registered_user_id' => 'id')),
            ),
            'indexes' => array(
                'invitation_address_idx' => array('address', 'address_type'),
                'invitation_user_id_idx' => array('user_id'),
                'invitation_registered_user_id_idx' => array('registered_user_id'),
            ),
        );
    }
}
