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
 * @author    Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2021-2022 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\Conversation\Controller;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Component\Collection\Util\Controller\FeedController;
use Component\Conversation\Entity\ConversationMute;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Conversation extends FeedController
{
    /**
     * Render conversation page.
     *
     * @param int $conversation_id To identify what Conversation is to be rendered
     *
     * @throws \App\Util\Exception\ServerException
     *
     * @return array Array containing keys: 'notes' (all known notes in the given Conversation), 'should_format' (boolean, stating if onFormatNoteList events may or not format given notes), 'page_title' (used as the title header)
     */
    public function showConversation(Request $request, int $conversation_id): array
    {
        return [
            '_template'     => 'collection/notes.html.twig',
            'notes'         => $this->query(query: "note-conversation:{$conversation_id}")['notes'] ?? [],
            'should_format' => false,
            'page_title'    => _m('Conversation'),
        ];
    }

    /**
     * Controller for the note reply non-JS page
     *
     * Leverages the `PostingModifyData` event to add the `reply_to_id` field from the GET variable 'reply_to_id'
     *
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws NoSuchNoteException
     * @throws ServerException
     *
     * @return array
     */
    public function addReply(Request $request)
    {
        $user            = Common::ensureLoggedIn();
        $note_id         = $this->int('reply_to_id', new ClientException(_m('Malformed query.')));
        $note            = Note::ensureCanInteract(Note::getByPK($note_id), $user);
        $conversation_id = $note->getConversationId();
        return $this->showConversation($request, $conversation_id);
    }

    /**
     * Creates form view for Muting Conversation extra action.
     *
     * @param int $conversation_id The Conversation id that this action targets
     *
     * @throws \App\Util\Exception\NoLoggedInUser
     * @throws \App\Util\Exception\RedirectException
     * @throws \App\Util\Exception\ServerException
     *
     * @return array Array containing templating where the form is to be rendered, and the form itself
     */
    public function muteConversation(Request $request, int $conversation_id)
    {
        $user     = Common::ensureLoggedIn();
        $is_muted = ConversationMute::isMuted($conversation_id, $user);
        $form     = Form::create([
            ['mute_conversation', SubmitType::class, ['label' => $is_muted ? _m('Unmute') : _m('Mute'), 'attr' => ['class' => '']]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$is_muted) {
                DB::persist(ConversationMute::create(['conversation_id' => $conversation_id, 'actor_id' => $user->getId()]));
            } else {
                DB::removeBy('conversation_mute', ['conversation_id' => $conversation_id, 'actor_id' => $user->getId()]);
            }
            DB::flush();
            Cache::delete(ConversationMute::cacheKeys($conversation_id, $user->getId())['mute']);

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$user->getId()} attempted to mute conversation {$conversation_id} and then get redirected to another host, or the URL was invalid ({$from})");
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                } else {
                    // TODO anchor on element id
                    throw new RedirectException(url: $from);
                }
            } else {
                // If we don't have a URL to return to, go to the instance root
                throw new RedirectException('root');
            }
        }

        return [
            '_template' => 'conversation/mute.html.twig',
            'notes'     => $this->query(query: "note-conversation:{$conversation_id}")['notes'] ?? [],
            'is_muted'  => $is_muted,
            'form'      => $form->createView(),
        ];
    }
}
