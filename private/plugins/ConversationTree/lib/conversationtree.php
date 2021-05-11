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
 * Conversation tree
 *
 * The widget class for displaying a hierarchical list of notices.
 *
 * @category  Widget
 * @package   ConversationTreePlugin
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ConversationTree extends NoticeList
{
    public $tree = null;
    public $table = null;

    /**
     * Show the tree of notices
     *
     * @return int
     */
    public function show(): int
    {
        $cnt = $this->_buildTree();

        $this->out->elementStart('div', ['id' => 'notices_primary']);
        // TRANS: Header on conversation page. Hidden by default (h2).
        $this->out->element('h2', null, _('Notices'));
        $this->out->elementStart('ol', ['class' => 'notices xoxo old-school']);

        if (array_key_exists('root', $this->tree)) {
            $rootid = $this->tree['root'][0];
            $this->showNoticePlus($rootid);
        }

        $this->out->elementEnd('ol');
        $this->out->elementEnd('div');

        return $cnt;
    }

    public function _buildTree(): int
    {
        $cnt = 0;

        $this->tree = [];
        $this->table = [];

        while ($this->notice->fetch()) {
            $cnt++;

            $id = $this->notice->id;
            $notice = clone($this->notice);

            $this->table[$id] = $notice;

            if (is_null($notice->reply_to)) {
                $this->tree['root'] = [$notice->id];
            } elseif (array_key_exists($notice->reply_to, $this->tree)) {
                $this->tree[$notice->reply_to][] = $notice->id;
            } else {
                $this->tree[$notice->reply_to] = [$notice->id];
            }
        }

        return $cnt;
    }

    /**
     * Shows a notice plus its list of children.
     *
     * @param integer $id ID of the notice to show
     *
     * @return void
     */
    public function showNoticePlus($id): void
    {
        $notice = $this->table[$id];

        $this->out->elementStart('li', ['class' => 'h-entry notice',
                                        'id'    => 'notice-' . $id]);

        $item = $this->newListItem($notice);
        $item->show();

        if (array_key_exists($id, $this->tree)) {
            $children = $this->tree[$id];

            $this->out->elementStart('ol', ['class' => 'notices threaded-replies xoxo']);

            sort($children);

            foreach ($children as $child) {
                $this->showNoticePlus($child);
            }

            $this->out->elementEnd('ol');
        }

        $this->out->elementEnd('li');
    }

    /**
     * Override parent class to return our preferred item.
     *
     * @param Notice $notice Notice to display
     *
     * @return ConversationTreeItem a list item to show
     */
    public function newListItem(Notice $notice): ConversationTreeItem
    {
        return new ConversationTreeItem($notice, $this->out);
    }
}
