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
 * Personal tag cloud section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Personal tag cloud section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SubscribersPeopleSelfTagCloudSection extends SubPeopleTagCloudSection
{
    public function title()
    {
        // TRANS: Title of personal tag cloud section.
        return _('People Tagcloud as self-tagged');
    }

    public function query()
    {
        return <<<'END'
            SELECT profile_tag.tag, COUNT(profile_tag.tag) AS weight
              FROM profile_tag
              INNER JOIN subscription AS sub
              ON profile_tag.tagger = sub.subscriber
              LEFT JOIN profile_list
              ON profile_tag.tag = profile_list.tag
              AND profile_tag.tagger = profile_list.tagger
              WHERE profile_tag.tagger = profile_tag.tagged
              AND profile_list.private IS NOT TRUE
              AND sub.subscribed = %d AND sub.subscribed <> sub.subscriber
              GROUP BY profile_tag.tag ORDER BY weight DESC;
            END;
    }
}
