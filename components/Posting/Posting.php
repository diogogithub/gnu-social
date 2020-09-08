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

namespace Component\Posting;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Module;
use App\Util\Common;
use App\Util\Exceptiion\InvalidFormException;
use App\Util\Exception\RedirectException;
use Component\Posting\Controller as C;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class Posting extends Module
{
    public function onAddRoute($r)
    {
        $r->connect('note_reply', '/note/reply/{reply_to<\d*>}', [C\Post::class, 'reply']);
    }

    public function onStartTwigPopulateVars(array &$vars)
    {
        if (($user = Common::user()) == null) {
            return;
        }

        $actor_id = $user->getId();
        $to_tags  = [];
        foreach (DB::dql('select c.tag from App\Entity\GSActorCircle c where c.tagger = :tagger', ['tagger' => $actor_id]) as $t) {
            $t           = $t['tag'];
            $to_tags[$t] = $t;
        }

        $placeholder_string = ['How are you feeling?', 'Have something to share?', 'How was your day?'];
        $rand_key           = array_rand($placeholder_string);

        $request = $vars['request'];
        $form    = Form::create([
            ['content',     TextareaType::class, ['label' => ' ', 'data' => '', 'attr' => ['placeholder' => _m($placeholder_string[$rand_key])]]],
            ['attachments', FileType::class,     ['label' => ' ', 'data' => null, 'multiple' => true, 'required' => false]],
            ['visibility',  ChoiceType::class,   ['label' => _m('Visibility:'), 'expanded' => true, 'choices' => [_m('Public') => 'public', _m('Instance') => 'instance', _m('Private') => 'private']]],
            ['to',          ChoiceType::class,   ['label' => _m('To:'), 'multiple' => true, 'expanded' => true, 'choices' => $to_tags]],
            ['post',        SubmitType::class,   ['label' => _m('Post')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid()) {
                C\Post::storeNote($actor_id, $data['content'], $data['attachments'], $is_local = true);
                throw new RedirectException();
            } else {
                throw new InvalidFormException();
            }
        }

        $vars['post_form'] = $form->createView();

        return Event::next;
    }
}
