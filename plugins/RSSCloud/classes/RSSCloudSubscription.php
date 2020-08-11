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
 * Table Definition for rsscloud_subscription
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class RSSCloudSubscription extends Managed_DataObject
{
    public $__table='rsscloud_subscription'; // table name
    public $subscribed;                      // int    primary key user id
    public $url;                             // string primary key
    public $failures;                        // int
    public $created;                         // datestamp()
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef(): array
    {
        return [
            'fields' => [
                'subscribed' => ['type' => 'int', 'not null' => true],
                'url' => ['type' => 'varchar', 'length' => '191', 'not null' => true],
                'failures' => ['type' => 'int', 'not null' => true, 'default' => 0],
                'created' => ['type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'],
            ],
            'primary key' => ['subscribed', 'url'],
            'foreign keys' => [
                'rsscloud_subscription_subscribed_fkey' => ['user', ['subscribed' => 'id']],
            ],
        ];
    }

    public static function getSubscription(
        int $subscribed,
        string $url
    ): ?self {
        $sub = new RSSCloudSubscription();
        $sub->whereAdd("subscribed = {$subscribed}");
        $sub->whereAdd("url = '{$sub->escape($url)}'");
        $sub->limit(1);

        if ($sub->find()) {
            $sub->fetch();
            return $sub;
        }

        return null;
    }
}
