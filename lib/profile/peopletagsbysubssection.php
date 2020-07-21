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
 * Peopletags with the most subscribers section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Peopletags with the most subscribers section
 *
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PeopletagsBySubsSection extends PeopletagSection
{
    public function getPeopletags()
    {
        $limit = PEOPLETAGS_PER_SECTION;

        $qry = <<<END
            SELECT profile_list.*, subscriber_count AS value
              FROM profile_list WHERE profile_list.private IS NOT TRUE
              ORDER BY value DESC
              LIMIT {$limit};
            END;

        $peopletag = Memcached_DataObject::cachedQuery('Profile_list', $qry, 3600);
        return $peopletag;
    }

    public function title()
    {
        // TRANS: Title for section contaning lists with the most subscribers.
        return _('Popular lists');
    }

    public function divId()
    {
        return 'top_peopletags_by_subs';
    }
}
