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

namespace Plugin\PinnedNotes\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Router\Router;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use Component\Collection\Util\Controller\FeedController;
use Plugin\PinnedNotes\Entity as E;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class PinnedNotes extends FeedController
{
    public function togglePin(Request $request, int $id)
    {
        $user = Common::ensureLoggedIn();
        $note = DB::findOneBy('note', ['id' => $id]);
        if ($user->getId() !== $note?->getActorId()) {
            throw new NoSuchNoteException();
        }

        $opts      = ['note_id' => $id, 'actor_id' => $user->getId()];
        $is_pinned = !\is_null(DB::findOneBy(E\PinnedNotes::class, $opts, return_null: true));

        $form = Form::create([
            ['toggle_pin', SubmitType::class, [
                'label' => _m(($is_pinned ? 'Unpin' : 'Pin') . ' this note'),
                'attr'  => [
                    'title' => _m(($is_pinned ? 'Unpin' : 'Pin') . ' this note'),
                ],
            ]],
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $opts = ['note_id' => $id, 'actor_id' => $user->getId()];
            if ($is_pinned) {
                $pinned = DB::findOneBy(E\PinnedNotes::class, $opts);
                DB::remove($pinned);
            } else {
                DB::persist(E\PinnedNotes::create($opts));
            }
            DB::flush();

            // redirect user to where they came from, but prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                } else {
                    throw new RedirectException(url: $from);
                }
            } else {
                // If we don't have a URL to return to, go to the instance root
                throw new RedirectException('root');
            }
        }

        return [
            '_template'   => 'PinnedNotes/toggle.html.twig',
            'note'        => $note,
            'title'       => _m($is_pinned ? 'Unpin note' : 'Pin note'),
            'toggle_form' => $form->createView(),
        ];
    }
}
