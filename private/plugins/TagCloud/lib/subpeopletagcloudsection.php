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

class SubPeopleTagCloudSection extends TagCloudSection
{
    public function getTags()
    {
        $qry = $this->query();
        $qry .= ' LIMIT ' . TAGS_PER_SECTION;

        $profile_tag = Memcached_DataObject::cachedQuery(
            'Profile_tag',
            sprintf($qry, $this->out->user->id)
        );
        return $profile_tag;
    }

    public function tagUrl($tag)
    {
        return common_local_url('peopletag', array('tag' => $tag));
    }

    public function showTag($tag, $weight, $relative)
    {
        $rel = 'tag-cloud-';
        $rel .= 1 + (int) (7 * $relative * $weight - 0.01);

        $this->out->elementStart('li', $rel);
        $this->out->element('a', array('href' => $this->tagUrl($tag)), $tag);
        $this->out->elementEnd('li');
    }
}
