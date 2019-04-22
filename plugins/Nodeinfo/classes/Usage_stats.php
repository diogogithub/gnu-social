<?php
/**
 * GNU social - a federating social network
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('GNUSOCIAL')) {
    exit(1);
}

/**
 * Table Definition for Usage_stats
 */
class Usage_stats extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'usage_stats';         // table name
    public $type;                            // varchar(191)  unique_key   not 255 because utf8mb4 takes more space
    public $count;                           // int(4)
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return [
            'description' => 'node stats',
            'fields' => [
                'type' => ['type' => 'varchar', 'length' => 191, 'description' => 'Type of countable entity'],
                'count' => ['type' => 'int', 'size' => 'int', 'default' => 0, 'description' => 'Number of entities of this type'],

                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['type'],
            'unique keys' => [
                'usage_stats_key' => ['type'],
            ],
            'indexes' => [
                'user_stats_idx' => ['type'],
            ],
        ];
    }

    public function getUserCount()
    {
        return intval(Usage_stats::getKV('type', 'users')->count);
    }

    public function getPostCount()
    {
        return intval(Usage_stats::getKV('type', 'posts')->count);
    }

    public function getCommentCount()
    {
        return intval(Usage_stats::getKV('type', 'comments')->count);
    }
}
