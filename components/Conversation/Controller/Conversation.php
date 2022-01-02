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
use Component\Feed\Feed;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        $data  = Feed::query(query: "note-conversation:{$conversation_id}", page: $this->int('p') ?? 1);
        $notes = $data['notes'];
        return [
            '_template'     => 'collection/notes.html.twig',
            'notes'         => $notes,
            'should_format' => false,
            'page_title'    => _m('Conversation'),
        ];
    }

    public function muteConversation(Request $request, int $conversation_id)
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
