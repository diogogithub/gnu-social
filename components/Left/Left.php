<?php

// {{{ License
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
// }}}

namespace Component\Left;

use App\Core\Event;
use App\Core\Module;
use App\Util\Common;
use Exception;

class Left extends Module
{
    public function onEndTwigPopulateVars(array &$vars)
    {
        try {
            $user = Common::user();
            if ($user != null) {
                $vars['user_nickname'] = $user->getNickname();
                $vars['user_tags']     = $user->getActor()->getSelfTags();
            }
        } catch (Exception $e) {
        }
        return Event::next;
    }
}
