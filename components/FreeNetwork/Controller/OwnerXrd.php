<?php

declare(strict_types = 1);
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

namespace Component\FreeNetwork\Controller;

/**
 * @package   WebFingerPlugin
 *
 * @author    James Walker <james@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

use App\Entity\LocalUser;
use App\Util\Common;
use Component\FreeNetwork\Util\Discovery;
use Component\FreeNetwork\Util\XrdController;
use Symfony\Component\HttpFoundation\Request;

class OwnerXrd extends XrdController
{
    protected string $default_mimetype = Discovery::XRD_MIMETYPE;

    public function handle(Request $request): array
    {
        $user = LocalUser::siteOwner();

        $nick           = common_canonical_nickname($user->nickname);
        $this->resource = 'acct:' . $nick . '@' . Common::config('site', 'server');

        // We have now set $args['resource'] to the configured value, since
        // only this local site configuration knows who the owner is!
        return parent::handle($request);
    }

    protected function setXRD()
    {
        // Check to see if a $config['webfinger']['owner'] has been set
        // and then make sure 'subject' is set to that primary identity.
        if (!empty($owner = Common::config('webfinger', 'owner'))) {
            $this->xrd->aliases[] = $this->xrd->subject;
            $this->xrd->subject   = Discovery::normalize($owner);
        } else {
            $this->xrd->subject = $this->resource;
        }
    }
}
