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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * ActivityPub's Pending follow requests
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_pending_follow_requests extends Managed_DataObject
{
    public $__table = 'activitypub_pending_follow_requests';
    public $local_profile_id;
    public $remote_profile_id;
    private $_reldb = null;

    /**
     * Return table definition for Schema setup and DB_DataObject usage.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return array array of column definitions
     */
    public static function schemaDef()
    {
        return [
            'fields' => [
                'local_profile_id'  => ['type' => 'integer', 'not null' => true],
                'remote_profile_id' => ['type' => 'integer', 'not null' => true],
                'relation_id'       => ['type' => 'serial',  'not null' => true],
            ],
            'primary key' => ['relation_id'],
            'foreign keys' => [
                'activitypub_pending_follow_requests_local_profile_id_fkey'  => ['profile', ['local_profile_id' => 'id']],
                'activitypub_pending_follow_requests_remote_profile_id_fkey' => ['profile', ['remote_profile_id' => 'id']],
            ],
        ];
    }

    public function __construct($actor, $remote_actor)
    {
        $this->local_profile_id  = $actor;
        $this->remote_profile_id = $remote_actor;
    }

    /**
     * Add Follow request to table.
     *
     * @return boolean true if added, false otherwise
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function add()
    {
        return !$this->exists() && $this->insert();
    }

    /**
     * Check if a Follow request is pending.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return boolean true if is pending, false otherwise
     */
    public function exists()
    {
        $this->_reldb = clone($this);
        if ($this->_reldb->find() > 0) {
            $this->_reldb->fetch();
            return true;
        }
        return false;
    }

    /**
     * Remove a request from the pending table.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return boolean true if removed, false otherwise
     */
    public function remove()
    {
        return $this->exists() && $this->_reldb->delete();
    }
}
