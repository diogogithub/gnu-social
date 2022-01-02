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

use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\ServerException;
use Component\Collection\Util\Controller\FeedController;
use Component\Feed\Feed;
use Symfony\Component\HttpFoundation\Request;
use function App\Core\I18n\_m;

class Reply extends FeedController
{
    /**
     * Controller for the note reply non-JS page
     *
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws NoSuchNoteException
     * @throws ServerException
     *
     * @return array
     */
    public function addReply(Request $request, int $note_id)
    {
        $user = Common::ensureLoggedIn();

        $note = Note::getByPK($note_id);
        if (\is_null($note) || !$note->isVisibleTo($user)) {
            throw new NoSuchNoteException();
        }

        $conversation_id = $note->getConversationId();
        $data  = Feed::query(query: "note-conversation:{$conversation_id}", page: $this->int('p') ?? 1);
        $notes = $data['notes'];
        return [
            '_template'     => 'collection/notes.html.twig',
            'notes'         => $notes,
            'should_format' => false,
            'page_title'    => _m('Conversation'),
        ];
    }
}
