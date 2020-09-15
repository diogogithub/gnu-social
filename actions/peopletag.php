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
 * Lists by a user
 *
 * @category  Personal
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @author    Shashi Gowda <connect2shashi@gmail.com>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/lib/profile/peopletaglist.php';
// cache 3 pages
define('PEOPLETAG_CACHE_WINDOW', PEOPLETAGS_PER_PAGE*3 + 1);

class PeopletagAction extends Action
{
    public $page = null;
    public $tag = null;

    public function isReadOnly($args)
    {
        return true;
    }

    public function title()
    {
        if ($this->page == 1) {
            // TRANS: Title for list page.
            // TRANS: %s is a list.
            return sprintf(_('Public list %s'), $this->tag);
        } else {
            // TRANS: Title for list page.
            // TRANS: %1$s is a list, %2$d is a page number.
            return sprintf(_('Public list %1$s, page %2$d'), $this->tag, $this->page);
        }
    }

    public function prepare(array $args = [])
    {
        parent::prepare($args);
        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        $tag_arg = $this->arg('tag');
        $tag = common_canonical_tag($tag_arg);

        // Permanent redirect on non-canonical nickname

        if ($tag_arg != $tag) {
            $args = array('tag' => $nickname);
            if ($this->page && $this->page != 1) {
                $args['page'] = $this->page;
            }
            common_redirect(common_local_url('peopletag', $args), 301);
        }
        $this->tag = $tag;

        return true;
    }

    public function handle()
    {
        parent::handle();
        $this->showPage();
    }

    public function showLocalNav()
    {
        $nav = new PublicGroupNav($this);
        $nav->show();
    }

    public function showAnonymousMessage()
    {
        $notice =
          // TRANS: Message for anonymous users on list page.
          // TRANS: This message contains Markdown links in the form [description](link).
          _('Lists are how you sort similar ' .
            'people on %%site.name%%, a [micro-blogging]' .
            '(http://en.wikipedia.org/wiki/Micro-blogging) service ' .
            'based on the Free Software [StatusNet](http://status.net/) tool. ' .
            'You can then easily keep track of what they ' .
            "are doing by subscribing to the list's timeline.");
        $this->elementStart('div', array('id' => 'anon_notice'));
        $this->raw(common_markup_to_html($notice));
        $this->elementEnd('div');
    }

    public function showContent()
    {
        $offset = ($this->page-1) * PEOPLETAGS_PER_PAGE;
        $limit  = PEOPLETAGS_PER_PAGE + 1;

        $ptags = new Profile_list();
        $ptags->tag = $this->tag;
        $ptags->orderBy('profile_list.modified DESC, profile_list.tagged DESC');

        $user = common_current_user();

        if (empty($user)) {
            $ckey = sprintf('profile_list:tag:%s', $this->tag);
            $ptags->private = false;

            $c = Cache::instance();
            if ($offset+$limit <= PEOPLETAG_CACHE_WINDOW && !empty($c)) {
                $cached_ptags = Profile_list::getCached($ckey, $offset, $limit);
                if ($cached_ptags === false) {
                    $ptags->limit(0, PEOPLETAG_CACHE_WINDOW);
                    $ptags->find();

                    Profile_list::setCache($ckey, $ptags, $offset, $limit);
                } else {
                    $ptags = clone($cached_ptags);
                }
            } else {
                $ptags->limit($offset, $limit);
                $ptags->find();
            }
        } else {
            $ptags->whereAdd(sprintf(
                <<<'END'
                (
                  (profile_list.tagger = %d AND profile_list.private IS TRUE)
                  OR profile_list.private IS NOT TRUE
                )
                END,
                $user->getID()
            ));

            $ptags->find();
        }

        $pl = new PeopletagList($ptags, $this);
        $cnt = $pl->show();

        $this->pagination(
            ($this->page > 1),
            ($cnt > PEOPLETAGS_PER_PAGE),
            $this->page,
            'peopletag',
            ['tag' => $this->tag]
        );
    }

    public function showSections()
    {
    }
}
