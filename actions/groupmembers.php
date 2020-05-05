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
 * List of group members
 *
 * @category  Group
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * List of group members
 *
 * @category  Group
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class GroupmembersAction extends GroupAction
{
    public $page = null;

    public function isReadOnly($args)
    {
        return true;
    }

    public function title()
    {
        if ($this->page == 1) {
            // TRANS: Title of the page showing group members.
            // TRANS: %s is the name of the group.
            return sprintf(
                _('%s group members'),
                $this->group->nickname
            );
        } else {
            // TRANS: Title of the page showing group members.
            // TRANS: %1$s is the name of the group, %2$d is the page number of the members list.
            return sprintf(
                _('%1$s group members, page %2$d'),
                $this->group->nickname,
                $this->page
            );
        }
    }

    public function showPageNotice()
    {
        $this->element(
            'p',
            'instructions',
            // TRANS: Page notice for group members page.
            _('A list of the users in this group.')
        );
    }

    public function showContent()
    {
        $offset = ($this->page-1) * PROFILES_PER_PAGE;
        $limit  = PROFILES_PER_PAGE + 1;

        $cnt = 0;

        $members = $this->group->getMembers($offset, $limit);

        if ($members) {
            $member_list = new GroupMemberList($members, $this->group, $this);
            $cnt = $member_list->show();
        }

        $this->pagination(
            $this->page > 1,
            $cnt > PROFILES_PER_PAGE,
            $this->page,
            'groupmembers',
            ['nickname' => $this->group->nickname]
        );
    }
}
