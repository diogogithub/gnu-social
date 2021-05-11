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
 * User by ID action class.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Robin Millette <millette@status.net>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * User by ID action class.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Robin Millette <millette@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class UserbyidAction extends ShowstreamAction
{
    protected function doPreparation()
    {
        // accessing by ID just requires an ID, not a nickname
        $this->target = Profile::getByID($this->trimmed('id'));

        // For local users when accessed by id number, redirect with
        // the nickname as argument instead of id.
        if ($this->target->isLocal()) {
            // Support redirecting to FOAF rdf/xml if the agent prefers it...
            // Internet Explorer doesn't specify "text/html" and does list "*/*"
            // at least through version 8. We need to list text/html up front to
            // ensure that only user-agents who specifically ask for RDF get it.
            $page_prefs = 'text/html,application/xhtml+xml,application/rdf+xml,application/xml;q=0.3,text/xml;q=0.2';
            $httpaccept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
            $type = common_negotiate_type(
                common_accept_to_prefs($httpaccept),
                common_accept_to_prefs($page_prefs)
            );
            $page = ($type === 'application/rdf+xml') ? 'foaf' : 'showstream';
            $url = common_local_url($page, ['nickname' => $this->target->getNickname()]);
            common_redirect($url, 303);
        }
    }
}
