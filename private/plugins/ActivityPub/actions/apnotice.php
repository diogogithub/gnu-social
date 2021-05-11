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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * Notice (Local notices only)
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class apNoticeAction extends ManagedAction
{
    protected $needLogin = false;
    protected $canPost   = true;

    /**
     * Notice id
     * @var int
     */
    public $notice_id;

    /**
     * Notice object to show
     */
    public $notice = null;

    /**
     * Profile of the notice object
     */
    public $profile = null;

    /**
     * Avatar of the profile of the notice object
     */
    public $avatar = null;

    /**
     * Load attributes based on database arguments
     *
     * Loads all the DB stuff
     *
     * @param array $args $_REQUEST array
     *
     * @return bool success flag
     */
    protected function prepare(array $args = []): bool
    {
        parent::prepare($args);

        $this->notice_id = (int)$this->trimmed('id');

        try {
            $this->notice = $this->getNotice();
        } catch (ClientException $e) {
            //ActivityPubReturn::error('Activity deleted.', 410);
            ActivityPubReturn::answer(Activitypub_tombstone::tombstone_to_array($this->notice_id), 410);
        }
        $this->target = $this->notice;

        if (!$this->notice->inScope($this->scoped)) {
            // TRANS: Client exception thrown when trying a view a notice the user has no access to.
            throw new ClientException(_m('Access restricted.'), 403);
        }

        $this->profile = $this->notice->getProfile();

        if (!$this->profile instanceof Profile) {
            // TRANS: Server error displayed trying to show a notice without a connected profile.
            $this->serverError(_m('Notice has no profile.'), 500);
        }

        try {
            $this->avatar = $this->profile->getAvatar(AVATAR_PROFILE_SIZE);
        } catch (Exception $e) {
            $this->avatar = null;
        }

        return true;
    }

    /**
     * Is this action read-only?
     *
     * @return bool true
     */
    public function isReadOnly($args): bool
    {
        return true;
    }

    /**
     * Last-modified date for page
     *
     * When was the content of this page last modified? Based on notice,
     * profile, avatar.
     *
     * @return int last-modified date as unix timestamp
     */
    public function lastModified(): int
    {
        return max(strtotime($this->notice->modified),
            strtotime($this->profile->modified),
            ($this->avatar) ? strtotime($this->avatar->modified) : 0);
    }

    /**
     * Handle the Notice request
     *
     * @return void
     * @throws EmptyPkeyValueException
     * @throws InvalidUrlException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    protected function handle(): void
    {
        if (is_null($this->notice)) {
            ActivityPubReturn::error('Invalid Activity URI.', 404);
        }
        if (!$this->notice->isLocal()) {
            // We have no authority on the requested activity.
            ActivityPubReturn::error("This is not a local activity.", 403);
        }

        $res = Activitypub_notice::notice_to_array($this->notice);

        ActivityPubReturn::answer($res);
    }

    /**
     * Fetch the notice to show. This may be overridden by child classes to
     * customize what we fetch without duplicating all of the prepare() method.
     *
     * @return null|Notice null if not found
     * @throws ClientException If GONE
     */
    protected function getNotice(): ?Notice
    {
        $notice = null;
        try {
            $notice = Notice::getByID($this->notice_id);
            // Alright, got it!
            return $notice;
        } catch (NoResultException $e) {
            // Hm, not found.
            $deleted = null;
            Event::handle('IsNoticeDeleted', [$this->notice_id, &$deleted]);
            if ($deleted === true) {
                // TRANS: Client error displayed trying to show a deleted notice.
                throw new ClientException(_m('Notice deleted.'), 410);
            }
        }
        // No such notice.
        return null;
    }
}
