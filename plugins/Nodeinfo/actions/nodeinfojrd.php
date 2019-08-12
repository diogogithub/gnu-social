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
 * Provided in /.well-known/nodeinfo
 *
 * @package   NodeInfo
 * @author    Stéphane Bérubé <chimo@chromic.org>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * JRD document for NodeInfo
 *
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NodeinfoJRDAction extends XrdAction
{
    const NODEINFO_2_0_REL = 'http://nodeinfo.diaspora.software/ns/schema/2.0';

    protected $defaultformat = 'json';

    protected function setXRD()
    {
        $this->xrd->links[] = new XML_XRD_Element_link(self::NODEINFO_2_0_REL, common_local_url('nodeinfo_2_0'));
    }
}
