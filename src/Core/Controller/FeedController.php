<?php

declare(strict_types = 1);

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

/**
 * Base class for feed controllers
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\Controller;

use App\Core\Controller;
use App\Core\Event;
use App\Util\Common;

abstract class FeedController extends Controller
{
    /**
     * Post process the result of a feed controller, to remove any
     * notes or actors the user specified, as well as format the raw
     * list of notes into a usable format
     */
    public static function post_process(array $result)
    {
        $actor = Common::actor();

        if (\array_key_exists('notes', $result)) {
            $notes = $result['notes'];
            if (!\is_null($actor)) {
                //Event::handle('FilterNoteList', [$actor, &$notes]);
            }

            if ($result['should_format'] ?? true) {
                $notes_out = null;
                Event::handle('FormatNoteList', [$notes, &$notes_out]);
                $result['notes'] = $notes_out;
            }
        }

        return $result;
    }
}
