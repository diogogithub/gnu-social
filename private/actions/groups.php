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
 * Latest groups information
 *
 * @category  Personal
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/lib/groups/grouplist.php';

/**
 * Latest groups
 *
 * Show the latest groups on the site
 *
 * @category  Personal
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class GroupsAction extends Action
{
    public $page = null;
    public $profile = null;

    public function isReadOnly($args)
    {
        return true;
    }

    public function title()
    {
        if ($this->page == 1) {
            // TRANS: Title for first page of the groups list.
            return _m('TITLE', 'Groups');
        } else {
            // TRANS: Title for all but the first page of the groups list.
            // TRANS: %d is the page number.
            return sprintf(_m('TITLE', 'Groups, page %d'), $this->page);
        }
    }

    public function prepare(array $args = [])
    {
        parent::prepare($args);
        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;
        return true;
    }

    public function handle()
    {
        parent::handle();
        $this->showPage();
    }

    public function showPageNotice()
    {
        $notice =
          // TRANS: Page notice of group list. %%%%site.name%%%% is the StatusNet site name,
          // TRANS: %%%%action.groupsearch%%%% and %%%%action.newgroup%%%% are URLs. Do not change them.
          // TRANS: This message contains Markdown links in the form [link text](link).
          sprintf(_('%%%%site.name%%%% groups let you find and talk with ' .
                    'people of similar interests. After you join a group ' .
                    'you can send messages to all other members using the ' .
                    'syntax "!groupname". Don\'t see a group you like? Try ' .
                    '[searching for one](%%%%action.groupsearch%%%%) or ' .
                    '[start your own](%%%%action.newgroup%%%%)!'));
        $this->elementStart('div', 'instructions');
        $this->raw(common_markup_to_html($notice));
        $this->elementEnd('div');
    }

    public function showContent()
    {
        if (common_logged_in()) {
            $this->elementStart('p', ['id' => 'new_group']);
            $this->element(
                'a',
                [
                    'href'  => common_local_url('newgroup'),
                    'class' => 'more',
                ],
                // TRANS: Link to create a new group on the group list page.
                _('Create a new group')
            );
            $this->elementEnd('p');
        }

        $offset = ($this->page-1) * GROUPS_PER_PAGE;
        $limit  = GROUPS_PER_PAGE + 1;

        $groups = new User_group();
        $groups->selectAdd();
        $groups->selectAdd('user_group.*');
        $groups->joinAdd(['id', 'local_group:group_id']);
        $groups->orderBy('user_group.created DESC, user_group.id DESC');
        $groups->limit($offset, $limit);
        $groups->find();

        $gl = new GroupList($groups, null, $this);
        $cnt = $gl->show();

        $this->pagination(
            $this->page > 1,
            $cnt > GROUPS_PER_PAGE,
            $this->page,
            'groups'
        );
    }

    public function showSections()
    {
        $gbp = new GroupsByPostsSection($this);
        $gbp->show();
        $gbm = new GroupsByMembersSection($this);
        $gbm->show();
    }
}
