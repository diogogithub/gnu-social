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
 * Show up to 100 favs of a notice
 *
 * @category  API
 * @package   GNUsocial
 * @author    Hannes Mannerheim <h@nnesmannerhe.im>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Show up to 100 favs of a notice
 *
 * @package   GNUsocial
 * @author    Hannes Mannerheim <h@nnesmannerhe.im>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ApiStatusesFavsAction extends ApiAuthAction
{
    const MAXCOUNT = 100;

    // Notice object for which to retrieve favs
    public $original = null;
    public $cnt      = self::MAXCOUNT;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    protected function prepare(array $args = [])
    {
        parent::prepare($args);

        if ($this->format !== 'json') {
            $this->clientError('This method currently only serves JSON.', 415);
        }

        $id = $this->trimmed('id');

        $this->original = Notice::getKV('id', $id);

        if (!($this->original instanceof Notice)) {
            // TRANS: Client error displayed trying to display redents of a non-exiting notice.
            $this->clientError(_('No such notice.'), 400);
        }

        $cnt = $this->trimmed('count');

        if (empty($cnt) || !is_integer($cnt)) {
            $cnt = 100;
        } else {
            $this->cnt = min((int)$cnt, self::MAXCOUNT);
        }

        return true;
    }

    /**
     * Handle the request
     *
     * Get favs and return them as json object
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */
    protected function handle()
    {
        parent::handle();

        $fave = new Fave();
        $fave->selectAdd();
        $fave->selectAdd('user_id');
        $fave->notice_id = $this->original->id;
        $fave->orderBy('modified, user_id');
        if (!is_null($this->cnt)) {
            $fave->limit(0, $this->cnt);
        }

        $ids = $fave->fetchAll('user_id');

        // Get nickname and profile image.
        $ids_with_profile_data = [];
        foreach (array_values($ids) as $i => $id) {
            $profile = Profile::getKV('id', $id);
            $ids_with_profile_data[$i]['user_id'] = $id;
            $ids_with_profile_data[$i]['nickname'] = $profile->nickname;
            $ids_with_profile_data[$i]['fullname'] = $profile->fullname;
            $ids_with_profile_data[$i]['profileurl'] = $profile->profileurl;
            $profile = new Profile();
            $profile->id = $id;
            $avatarurl = $profile->avatarUrl(24);
            $ids_with_profile_data[$i]['avatarurl'] = $avatarurl;
        }

        $this->initDocument('json');
        $this->showJsonObjects($ids_with_profile_data);
        $this->endDocument('json');
    }

    /**
     * Return true if read only.
     *
     * MAY override
     *
     * @param array $args other arguments
     *
     * @return boolean is read only action?
     */

    public function isReadOnly($args)
    {
        return true;
    }
}
