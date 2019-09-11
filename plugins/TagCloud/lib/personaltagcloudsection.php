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
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PersonalTagCloudSection extends TagCloudSection
{
    protected $profile = null;

    public function __construct(HTMLOutputter $out, Profile $profile)
    {
        parent::__construct($out);
        $this->profile = $profile;
    }

    public function title()
    {
        // TRANS: Title for personal tag cloud section.
        return _m('TITLE', 'Tags');
    }

    public function getTags()
    {
        $weightexpr = common_sql_weight('notice_tag.created', common_config('tag', 'dropoff'));
        // @fixme should we use the cutoff too? Doesn't help with indexing per-user.

        $limit = TAGS_PER_SECTION;

        $qry = 'SELECT notice_tag.tag, ' . $weightexpr . ' AS weight ' .
            'FROM notice_tag INNER JOIN notice ' .
            'ON notice_tag.notice_id = notice.id ' .
            'WHERE notice.profile_id = %d ' .
            'GROUP BY notice_tag.tag ' .
            'ORDER BY weight DESC LIMIT ' . $limit;

        $tag = Memcached_DataObject::cachedQuery(
            'Notice_tag',
            sprintf($qry, $this->profile->getID()),
            3600
        );
        return $tag;
    }
}
