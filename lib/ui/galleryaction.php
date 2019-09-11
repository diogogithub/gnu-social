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
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

// 10x8

define('AVATARS_PER_PAGE', 80);

// @todo FIXME: Class documentation missing.
class GalleryAction extends ProfileAction
{
    protected function handle()
    {
        // Post from the tag dropdown; redirect to a GET
        if ($this->isPost()) {
            common_redirect($this->selfUrl(), 303);
        }

        parent::handle();
    }

    public function showContent()
    {
        $this->showTagsDropdown();
    }

    public function showTagsDropdown()
    {
        $tag = $this->trimmed('tag');

        $tags = $this->getAllTags();

        $content = array();

        foreach ($tags as $t) {
            $content[$t] = $t;
        }
        if ($tags) {
            $this->elementStart('dl', array('id' => 'filter_tags'));
            $this->element('dt', null, _('Tags'));
            $this->elementStart('dd');
            $this->elementStart('ul');
            $this->elementStart('li', array('id' => 'filter_tags_all',
                                             'class' => 'child_1'));
            $this->element(
                'a',
                [
                    'href' => common_local_url(
                        $this->trimmed('action'),
                        ['nickname' => $this->target->getNickname()]
                    ),
                ],
                // TRANS: List element on gallery action page to show all tags.
                _m('TAGS', 'All')
            );
            $this->elementEnd('li');
            $this->elementStart('li', array('id'=>'filter_tags_item'));
            $this->elementStart('form', array('name' => 'bytag',
                                               'id' => 'form_filter_bytag',
                                              'action' => common_path('?action=' . $this->getActionName()),
                                               'method' => 'post'));
            $this->elementStart('fieldset');
            // TRANS: Fieldset legend on gallery action page.
            $this->element('legend', null, _('Select tag to filter'));
            // TRANS: Dropdown field label on gallery action page for a list containing tags.
            $this->dropdown(
                'tag',
                _('Tag'),
                $content,
                // TRANS: Dropdown field title on gallery action page for a list containing tags.
                _('Choose a tag to narrow list.'),
                false,
                $tag
            );
            $this->hidden('nickname', $this->target->getNickname());
            // TRANS: Submit button text on gallery action page.
            $this->submit('submit', _m('BUTTON', 'Go'));
            $this->elementEnd('fieldset');
            $this->elementEnd('form');
            $this->elementEnd('li');
            $this->elementEnd('ul');
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }
    }

    // Get list of tags we tagged other users with
    public function getTags($lst, $usr)
    {
        $profile_tag = new Notice_tag();
        $profile_tag->query('SELECT DISTINCT(tag) ' .
                            'FROM profile_tag, subscription ' .
                            'WHERE tagger = ' . $this->target->id . ' ' .
                            'AND ' . $usr . ' = ' . $this->target->id . ' ' .
                            'AND ' . $lst . ' = tagged ' .
                            'AND tagger <> tagged');
        $tags = array();
        while ($profile_tag->fetch()) {
            $tags[] = $profile_tag->tag;
        }
        $profile_tag->free();
        return $tags;
    }

    public function getAllTags()
    {
        return array();
    }

    public function showProfileBlock()
    {
        $block = new AccountProfileBlock($this, $this->target);
        $block->show();
    }
}
