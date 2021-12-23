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
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\Conversation\Controller;

use function App\Core\I18n\_m;
use Component\Feed\Feed;
use Component\Feed\Util\FeedController;
use Symfony\Component\HttpFoundation\Request;

class Conversation extends FeedController
{
    /**
     * Render conversation page
     *
     * @return array
     */
    public function showConversation(Request $request, int $conversation_id)
    {
        // TODO:
        // if note is root -> just link
        // if note is a reply -> link from above plus anchor

        /*        $notes = DB::findBy(
                    table: 'note',
                    criteria: ['conversation_id' => $conversation_id],
                    order_by: ['created' => 'DESC', 'id' => 'DESC']
                );*/
        $data  = Feed::query(query: "note-conversation:{$conversation_id}", page: $this->int('p') ?? 1);
        $notes = $data['notes'];
        return [
            '_template'     => 'feed/feed.html.twig',
            'notes'         => $notes,
            'should_format' => false,
            'page_title'    => _m('Conversation'),
        ];
    }
}
