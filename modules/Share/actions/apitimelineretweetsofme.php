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
 * Show authenticating user's most recent notices that have been repeated
 *
 * @category  API
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Show authenticating user's most recent notices that have been repeated
 *
 * @category  API
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ApiTimelineRetweetsOfMeAction extends ApiAuthAction
{
    const DEFAULTCOUNT = 20;
    const MAXCOUNT     = 200;
    const MAXNOTICES   = 3200;

    public $repeats  = null;
    public $cnt      = self::DEFAULTCOUNT;
    public $page     = 1;
    public $since_id = null;
    public $max_id   = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return bool success flag
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);

        $cnt = $this->int('count', self::DEFAULTCOUNT, self::MAXCOUNT, 1);

        $page = $this->int('page', 1, (self::MAXNOTICES/$this->cnt));

        $since_id = $this->int('since_id');

        $max_id = $this->int('max_id');

        return true;
    }

    /**
     * Handle the request
     *
     * show a timeline of the user's repeated notices
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $offset = ($this->page-1) * $this->cnt;
        $limit  = $this->cnt;

        // TRANS: Title of list of repeated notices of the logged in user.
        // TRANS: %s is the nickname of the logged in user.
        $title      = sprintf(_("Repeats of %s"), $this->auth_user->nickname);
        $sitename   = common_config('site', 'name');

        $profile = $this->auth_user->getProfile();

        $subtitle   = sprintf(
            // TRANS: Subtitle of API time with retweets of me.
            // TRANS: %1$s is the StatusNet sitename, %2$s is the user nickname, %3$s is the user profile name.
            _('%1$s notices that %2$s / %3$s has repeated.'),
            $sitename,
            $this->auth_user->nickname,
            $profile->getBestName()
        );

        $taguribase = TagURI::base();
        $id         = "tag:$taguribase:RepeatsOfMe:" . $this->auth_user->id;

        $link = common_local_url(
            'all',
            ['nickname' => $this->auth_user->nickname]
        );

        $strm = $this->auth_user->repeatsOfMe(
            $offset,
            $limit,
            $this->since_id,
            $this->max_id
        );

        switch ($this->format) {
        case 'xml':
            $this->showXmlTimeline($strm);
            break;
        case 'json':
            $this->showJsonTimeline($strm);
            break;
        case 'atom':
            header('Content-Type: application/atom+xml; charset=utf-8');
            $atom = new AtomNoticeFeed($this->auth_user);
            $atom->setId($id);
            $atom->setTitle($title);
            $atom->setSubtitle($subtitle);
            $atom->setUpdated('now');
            $atom->addLink($link);
            $atom->setSelfLink($this->getSelfUri());
            $atom->addEntryFromNotices($strm);
            $this->raw($atom->getString());
            break;
        case 'as':
            header('Content-Type: ' . ActivityStreamJSONDocument::CONTENT_TYPE);
            $doc = new ActivityStreamJSONDocument($this->auth_user);
            $doc->setTitle($title);
            $doc->addLink($link, 'alternate', 'text/html');
            $doc->addItemsFromNotices($strm);
            $this->raw($doc->asString());
            break;
        default:
            // TRANS: Client error displayed when coming across a non-supported API method.
            $this->clientError(_('API method not found.'), 404);
            break;
        }
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
