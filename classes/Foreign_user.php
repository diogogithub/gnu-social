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
 * Table Definition for foreign_user
 */

defined('GNUSOCIAL') || die();

class Foreign_user extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'foreign_user';                    // table name
    public $id;                              // bigint(8)  primary_key not_null
    public $service;                         // int(4)  primary_key not_null
    public $uri;                             // varchar(191)  unique_key not_null   not 255 because utf8mb4 takes more space
    public $nickname;                        // varchar(191)   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'int', 'size' => 'big', 'not null' => true, 'description' => 'unique numeric key on foreign service'),
                'service' => array('type' => 'int', 'not null' => true, 'description' => 'foreign key to service'),
                'uri' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'identifying URI'),
                'nickname' => array('type' => 'varchar', 'length' => 191, 'description' => 'nickname on foreign service'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('id', 'service'),
            'foreign keys' => array(
                'foreign_user_service_fkey' => array('foreign_service', array('service' => 'id')),
            ),
            'unique keys' => array(
                'foreign_user_uri_key' => array('uri'),
            ),
        );
    }

    public static function getForeignUser($id, $service)
    {
        if (empty($id) || empty($service)) {
            throw new ServerException('Empty foreign user id or service for Foreign_user::getForeignUser');
        }

        $fuser = new Foreign_user();
        $fuser->id      = $id;
        $fuser->service = $service;
        $fuser->limit(1);

        if (!$fuser->find(true)) {
            throw new NoResultException($fuser);
        }

        return $fuser;
    }

    public static function getByNickname($nickname, $service)
    {
        if (empty($nickname) || empty($service)) {
            throw new ServerException('Empty nickname or service for Foreign_user::getByNickname');
        }

        $fuser = new Foreign_user();
        $fuser->service = $service;
        $fuser->nickname = $nickname;
        $fuser->limit(1);

        if (!$fuser->find(true)) {
            throw new NoResultException($fuser);
        }

        return $fuser;
    }
}
