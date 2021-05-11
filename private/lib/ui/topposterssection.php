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
 * Base class for sections showing lists of people
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Base class for sections
 *
 * These are the widgets that show interesting data about a person
 * group, or site.
 *
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class TopPostersSection extends ProfileSection
{
    public function getProfiles()
    {
        $limit = PROFILES_PER_SECTION;

        $qry = sprintf(
            <<<'END'
            SELECT *
              FROM profile INNER JOIN (
                SELECT profile_id AS id, COUNT(profile_id) AS value
                  FROM notice
                  %1$sGROUP BY profile_id
              ) AS t1 USING (id)
              ORDER BY value DESC LIMIT %2$d;
            END,
            (common_config('public', 'localonly') ? 'WHERE notice.is_local = 1 ' : ''),
            $limit
        );

        $profile = Memcached_DataObject::cachedQuery('Profile', $qry, 6 * 3600);
        return $profile;
    }

    public function title()
    {
        // TRANS: Title for top posters section.
        return _('Top posters');
    }

    public function divId()
    {
        return 'top_posters';
    }
}
