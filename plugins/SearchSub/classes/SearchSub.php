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
 * Data class to store local search subscriptions
 *
 * @category  Plugin
 * @package   SearchSubPlugin
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * For storing the search subscriptions
 *
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      DB_DataObject
 */
class SearchSub extends Managed_DataObject
{
    public $__table = 'searchsub'; // table name
    public $search;         // text
    public $profile_id;  // int -> profile.id
    public $created;     // datetime

    /**
     * The One True Thingy that must be defined and declared.
     */
    public static function schemaDef()
    {
        return array(
            'description' => 'SearchSubPlugin search subscription records',
            'fields' => array(
                'search' => array('type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'hash search associated with this subscription'),
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'profile ID of subscribing user'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
            ),
            'primary key' => array('search', 'profile_id'),
            'foreign keys' => array(
                'searchsub_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
            'indexes' => array(
                'searchsub_created_idx' => array('created'),
                'searchsub_profile_id_tag_idx' => array('profile_id', 'search'),
            ),
        );
    }

    /**
     * Start a search subscription!
     *
     * @param profile $profile subscriber
     * @param string $search subscribee
     * @return SearchSub
     */
    public static function start(Profile $profile, $search)
    {
        $ts = new SearchSub();
        $ts->search = $search;
        $ts->profile_id = $profile->id;
        $ts->created = common_sql_now();
        $ts->insert();
        self::blow('searchsub:by_profile:%d', $profile->id);
        return $ts;
    }

    /**
     * End a search subscription!
     *
     * @param profile $profile subscriber
     * @param string $search subscribee
     */
    public static function cancel(Profile $profile, $search)
    {
        $ts = SearchSub::pkeyGet(array('search' => $search,
            'profile_id' => $profile->id));
        if ($ts) {
            $ts->delete();
            self::blow('searchsub:by_profile:%d', $profile->id);
        }
    }

    public static function forProfile(Profile $profile)
    {
        $searches = array();

        $keypart = sprintf('searchsub:by_profile:%d', $profile->id);
        $searchstring = self::cacheGet($keypart);

        if ($searchstring !== false) {
            if (!empty($searchstring)) {
                $searches = explode(',', $searchstring);
            }
        } else {
            $searchsub = new SearchSub();
            $searchsub->profile_id = $profile->id;
            $searchsub->selectAdd();
            $searchsub->selectAdd('search');

            if ($searchsub->find()) {
                $searches = $searchsub->fetchAll('search');
            }

            self::cacheSet($keypart, implode(',', $searches));
        }

        return $searches;
    }
}
