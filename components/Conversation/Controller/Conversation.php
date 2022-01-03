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

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\RedirectException;
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
        $data  = $this->query(query: "note-conversation:{$conversation_id}");
        $notes = $data['notes'];

        return [
            '_template'     => 'collection/notes.html.twig',
            'notes'         => $notes,
            'should_format' => false,
            'page_title'    => _m('Conversation'),
        ];
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
    public function muteConversation(Request $request, int $conversation_id): array
    {
        $user = Common::ensureLoggedIn();
        $form = Form::create([
            ['mute_conversation', SubmitType::class, ['label' => _m('Mute conversation')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            DB::persist(ConversationMute::create(['conversation_id' => $conversation_id, 'actor_id' => $user->getId()]));
            DB::flush();
            throw new RedirectException();
        }

        return [
            '_template' => 'conversation/mute.html.twig',
            'form'      => $form->createView(),
        ];
    }
}
