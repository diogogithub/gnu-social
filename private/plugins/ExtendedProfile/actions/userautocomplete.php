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
 * Action for showing Twitter-like JSON search results
 *
 * @category  Search
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class UserautocompleteAction extends Action
{
    public $query;

    /**
     * Initialization.
     *
     * @param array $args Web and URL arguments
     *
     * @return boolean true if nothing goes wrong
     * @throws ClientException
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);
        $this->query = $this->trimmed('term');
        return true;
    }

    /**
     * Handle a request
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();
        $this->showResults();
    }

    /**
     * Search for users matching the query and spit the results out
     * as a quick-n-dirty JSON document
     *
     * @return void
     * @throws ServerException
     */
    public function showResults()
    {
        $people = array();

        $profile = new Profile();

        $search_engine = $profile->getSearchEngine('profile');
        $search_engine->set_sort_mode('nickname_desc');
        $search_engine->limit(0, 10);
        $search_engine->query(strtolower($this->query . '*'));

        $cnt = $profile->find();

        if ($cnt > 0) {
            $sql = 'SELECT profile.* FROM profile, user WHERE profile.id = user.id '
                . ' AND LEFT(LOWER(profile.nickname), '
                . strlen($this->query)
                . ') = \'%s\' '
                . ' LIMIT 10';

            $profile->query(sprintf($sql, $this->query));
        }

        while ($profile->fetch()) {
            $people[] = $profile->nickname;
        }

        header('Content-Type: application/json; charset=utf-8');
        print json_encode($people);
    }

    /**
     * Do we need to write to the database?
     *
     * @param $args
     * @return boolean true
     */
    public function isReadOnly($args)
    {
        return true;
    }
}
