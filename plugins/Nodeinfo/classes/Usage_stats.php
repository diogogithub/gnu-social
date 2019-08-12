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
 * Table for storing Nodeinfo statistics
 *
 * @package   NodeInfo
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Table Definition for usage_stats and some getters
 *
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Usage_stats extends Managed_DataObject
{
    public $__table = 'usage_stats';         // table name
    public $type;                            // varchar(191)  unique_key   not 255 because utf8mb4 takes more space
    public $count;                           // int(4)
    public $modified;                        // datetime()   not_null default_CURRENT_TIMESTAMP

    /**
     * Table Definition for usage_stats
     *
     * @return array
     */
    public static function schemaDef(): array
    {
        return [
            'description' => 'node stats',
            'fields' => [
                'type' => ['type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'Type of countable entity'],
                'count' => ['type' => 'int', 'size' => 'int', 'default' => 0, 'description' => 'Number of entities of this type'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['type'],
            'indexes' => [
                'user_stats_idx' => ['type'],
            ],
        ];
    }

    /**
     * Total number of users
     *
     * @return int
     */
    public function getUserCount(): int
    {
        return Usage_stats::getKV('type', 'users')->count;
    }

    /**
     * Total number of dents
     *
     * @return int
     */
    public function getPostCount(): int
    {
        return Usage_stats::getKV('type', 'posts')->count;
    }

    /**
     * Total number of replies
     *
     * @return int
     */
    public function getCommentCount(): int
    {
        return Usage_stats::getKV('type', 'comments')->count;
    }
}
