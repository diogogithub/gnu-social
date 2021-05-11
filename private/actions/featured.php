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
 * List of featured users
 *
 * @category  Public
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/lib/profile/profilelist.php';
require_once INSTALLDIR . '/lib/groups/publicgroupnav.php';

/**
 * List of featured users
 *
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FeaturedAction extends Action
{
    public $page = null;

    public function isReadOnly($args)
    {
        return true;
    }

    public function prepare(array $args = [])
    {
        parent::prepare($args);
        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        return true;
    }

    public function title()
    {
        if ($this->page == 1) {
            // TRANS: Page title for first page of featured users.
            return _('Featured users');
        } else {
            // TRANS: Page title for all but first page of featured users.
            // TRANS: %d is the page number being displayed.
            return sprintf(_('Featured users, page %d'), $this->page);
        }
    }

    public function handle()
    {
        parent::handle();

        $this->showPage();
    }

    public function showPageNotice()
    {
        $instr = $this->getInstructions();
        $output = common_markup_to_html($instr);
        $this->elementStart('div', 'instructions');
        $this->raw($output);
        $this->elementEnd('div');
    }

    public function getInstructions()
    {
        // TRANS: Description on page displaying featured users.
        return sprintf(
            _('A selection of some great users on %s.'),
            common_config('site', 'name')
        );
    }

    public function showContent()
    {
        // XXX: Note I'm doing it this two-stage way because a raw query
        // with a JOIN was *not* working. --Zach

        $featured_nicks = common_config('nickname', 'featured');

        if (count($featured_nicks) > 0) {
            $quoted = array();

            foreach ($featured_nicks as $nick) {
                $quoted[] = "'$nick'";
            }

            $user = new User;
            $user->whereAdd(sprintf('nickname IN (%s)', implode(',', $quoted)));
            $user->limit(($this->page - 1) * PROFILES_PER_PAGE, PROFILES_PER_PAGE + 1);
            $user->orderBy($user->escapedTableName() . '.nickname ASC');

            $user->find();

            $profile_ids = array();

            while ($user->fetch()) {
                $profile_ids[] = $user->id;
            }

            $profile = new Profile;
            $profile->whereAdd(sprintf('profile.id IN (%s)', implode(',', $profile_ids)));
            $profile->orderBy('nickname ASC');

            $cnt = $profile->find();

            if ($cnt > 0) {
                $featured = new ProfileList($profile, $this);
                $featured->show();
            }

            $profile->free();

            $this->pagination(
                $this->page > 1,
                $cnt > PROFILES_PER_PAGE,
                $this->page,
                'featured'
            );
        }
    }
}
