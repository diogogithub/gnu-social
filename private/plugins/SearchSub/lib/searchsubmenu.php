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

defined('GNUSOCIAL') || die();

/**
 * Class comment
 *
 * @category  Plugin
 * @package   SearchSubPlugin
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SearchSubMenu extends MoreMenu
{
    protected $user;
    protected $searches;

    public function __construct($out, $user, $searches)
    {
        parent::__construct($out);
        $this->user = $user;
        $this->searches = $searches;
    }

    public function tag()
    {
        return 'searchsubs';
    }

    public function seeAllItem()
    {
        return array('searchsubs',
            array('nickname' => $this->user->nickname),
            _('See all'),
            _('See all searches you are following'));
    }

    public function getItems()
    {
        $items = array();

        foreach ($this->searches as $search) {
            if (!empty($search)) {
                $items[] = array('noticesearch',
                    array('q' => $search),
                    sprintf('"%s"', $search),
                    sprintf(_('Notices including %s'), $search));;
            }
        }

        return $items;
    }

    public function item($actionName, array $args, $label, $description, $id = null, $cls = null)
    {
        if (empty($id)) {
            $id = $this->menuItemID($actionName, $args);
        }

        if ($actionName == 'noticesearch') {
            // Add 'q' as a search param, not part of the url path
            $url = common_local_url($actionName, array(), $args);
        } else {
            $url = common_local_url($actionName, $args);
        }

        $this->out->menuItem(
            $url,
            $label,
            $description,
            $this->isCurrent($actionName, $args),
            $id,
            $cls
        );
    }
}
