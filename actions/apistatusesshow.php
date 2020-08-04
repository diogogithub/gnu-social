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
 * Show a notice (as a Twitter-style status)
 *
 * @category  API
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Jeffery To <jeffery.to@gmail.com>
 * @author    Tom Blankenship <mac65@mac65.com>
 * @author    Mike Cochrane <mikec@mikenz.geek.nz>
 * @author    Robin Millette <robin@millette.info>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Returns the notice specified by id as a Twitter-style status and inline user
 *
 * @category  API
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Jeffery To <jeffery.to@gmail.com>
 * @author    Tom Blankenship <mac65@mac65.com>
 * @author    Mike Cochrane <mikec@mikenz.geek.nz>
 * @author    Robin Millette <robin@millette.info>
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ApiStatusesShowAction extends ApiPrivateAuthAction
{
    public $notice_id = null;
    public $notice    = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    protected function prepare(array $args=array())
    {
        parent::prepare($args);

        // 'id' is an undocumented parameter in Twitter's API. Several
        // clients make use of it, so we support it too.

        // show.json?id=12345 takes precedence over /show/12345.json

        $this->notice_id = (int)$this->trimmed('id');

        $this->notice = null;
        try {
            $this->notice = Notice::getByID($this->notice_id);
        } catch (NoResultException $e) {
            // No such notice was found, maybe it was deleted?
            $deleted = null;
            Event::handle('IsNoticeDeleted', array($this->notice_id, &$deleted));
            if ($deleted === true) {
                // TRANS: Client error displayed trying to show a deleted notice.
                throw new ClientException(_('Notice deleted.'), 410);
            }
            // TRANS: Client error displayed trying to show a non-existing notice.
            throw new ClientException(_('No such notice.'), 404);
        }

        if (!$this->notice->inScope($this->scoped)) {
            // TRANS: Client exception thrown when trying a view a notice the user has no access to.
            throw new ClientException(_('Access restricted.'), 403);
        }

        return true;
    }

    /**
     * Handle the request
     *
     * Check the format and show the notice
     *
     * @return void
     */
    protected function handle()
    {
        parent::handle();

        if (!in_array($this->format, array('xml', 'json', 'atom'))) {
            // TRANS: Client error displayed when coming across a non-supported API method.
            $this->clientError(_('API method not found.'), 404);
        }

        switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $this->showNotice();
            break;
        case 'DELETE':
            $this->deleteNotice();
            break;
        default:
            // TRANS: Client error displayed calling an unsupported HTTP error in API status show.
            $this->clientError(_('HTTP method not supported.'), 405);
        }
    }

    /**
     * Show the notice
     *
     * @return void
     */
    public function showNotice()
    {
        switch ($this->format) {
        case 'xml':
            $this->showSingleXmlStatus($this->notice);
            break;
        case 'json':
            $this->show_single_json_status($this->notice);
            break;
        case 'atom':
            $this->showSingleAtomStatus($this->notice);
            break;
        default:
            // TRANS: Exception thrown requesting an unsupported notice output format.
            // TRANS: %s is the requested output format.
            throw new Exception(sprintf(_("Unsupported format: %s."), $this->format));
        }
    }

    /**
     * We expose AtomPub here, so non-GET/HEAD reqs must be read/write.
     *
     * @param array $args other arguments
     *
     * @return boolean true
     */

    public function isReadOnly($args)
    {
        return in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD']);
    }

    /**
     * When was this notice last modified?
     *
     * @return string datestamp of the latest notice in the stream
     */
    public function lastModified()
    {
        return strtotime($this->notice->created);
    }

    /**
     * An entity tag for this notice
     *
     * Returns an Etag based on the action name, language, and
     * timestamps of the notice
     *
     * @return string etag
     */
    public function etag()
    {
        return '"' . implode(
            ':',
            array($this->arg('action'),
                  common_user_cache_hash($this->auth_user),
                  common_language(),
                  $this->notice->id,
                  strtotime($this->notice->created))
        )
        . '"';
    }

    public function deleteNotice()
    {
        if ($this->format != 'atom') {
            // TRANS: Client error displayed when trying to delete a notice not using the Atom format.
            $this->clientError(_('Can only delete using the Atom format.'));
        }

        if (empty($this->auth_user) ||
            ($this->notice->profile_id != $this->auth_user->id &&
             !$this->auth_user->hasRight(Right::DELETEOTHERSNOTICE))) {
            // TRANS: Client error displayed when a user has no rights to delete notices of other users.
            $this->clientError(_('Cannot delete this notice.'), 403);
        }

        if (Event::handle('StartDeleteOwnNotice', array($this->auth_user, $this->notice))) {
            $this->notice->deleteAs($this->scoped);
            Event::handle('EndDeleteOwnNotice', array($this->auth_user, $this->notice));
        }

        // @fixme is there better output we could do here?

        http_response_code(200);
        header('Content-Type: text/plain');
        // TRANS: Confirmation of notice deletion in API. %d is the ID (number) of the deleted notice.
        print(sprintf(_('Deleted notice %d'), $this->notice->id));
        print("\n");
    }
}
