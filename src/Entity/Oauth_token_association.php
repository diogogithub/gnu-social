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
 * Table Definition for oauth_association
 */

defined('GNUSOCIAL') || die();

class Oauth_token_association extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'oauth_token_association';          // table name
    public $profile_id;                      // int(4)  primary_key not_null
    public $application_id;                  // int(4)  primary_key not_null
    public $token;                           // varchar(191) primary key not null   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function getByUserAndToken($user, $token)
    {
        if (empty($user) || empty($token)) {
            return null;
        }

        $oau = new oauth_request_token();

        $oau->profile_id = $user->id;
        $oau->token      = $token;
        $oau->limit(1);

        $result = $oau->find(true);

        return empty($result) ? null : $oau;
    }

    public static function schemaDef()
    {
        return array(
            'description' => 'Associate an application ID and profile ID with an OAuth token',
            'fields' => array(
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'associated user'),
                'application_id' => array('type' => 'int', 'not null' => true, 'description' => 'the application'),
                'token' => array('type' => 'varchar', 'length' => '191', 'not null' => true, 'description' => 'token used for this association'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('profile_id', 'application_id', 'token'),
            'foreign keys' => array(
                'oauth_token_association_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
                'oauth_token_association_application_id_fkey' => array('oauth_application', array('application_id' => 'id')),
            ),
            'indexes' => array(
                'oauth_token_association_application_id_idx' => array('application_id'),
            ),
        );
    }
}
