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

namespace Component\Posting\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Security;
use App\Entity\FileToNote;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use Component\Media\Media;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class Post
{
    public function reply(Request $request, string $reply_to)
    {
        $note = DB::find('note', ['id' => $reply_to]);
        if ($note == null) {
            throw new ClientException(_m('No such note'));
        }

        $actor_id = Common::ensureLoggedIn()->getId();

        $form = Form::create([
            ['reply_to',    HiddenType::class,   ['data' => (int) $reply_to]],
            ['content',     TextareaType::class, ['label' => ' ']],
            ['attachments', FileType::class,     ['label' => ' ', 'multiple' => true, 'required' => false]],
            ['reply',       SubmitType::class,   ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid()) {
                self::storeNote($actor_id, $data['content'], $data['attachments'], $is_local = true, $data['reply_to'], null);
            } else {
                // TODO display errors
            }
        }

        return [
            '_template' => 'note/reply.html.twig',
            'note'      => $note,
            'reply'     => $form->createView(),
        ];
    }

    public static function storeNote(int $actor_id, string $content, array $attachments, bool $is_local, ?int $reply_to = null, ?int $repeat_of = null)
    {
        $note  = Note::create(['gsactor_id' => $actor_id, 'content' => $content, 'is_local' => $is_local, 'reply_to' => $reply_to, 'repeat_of' => $repeat_of]);
        $files = [];
        foreach ($attachments as $f) {
            $nf = Media::validateAndStoreFile($f, Common::config('attachments', 'dir'),
                                              Security::sanitize($title = $f->getClientOriginalName()),
                                              $is_local = true, $actor_id);
            $files[] = $nf;
            DB::persist($nf);
        }
        DB::persist($note);
        // Need file and note ids for the next step
        DB::flush();
        if ($attachments != []) {
            foreach ($files as $f) {
                DB::persist(FileToNote::create(['file_id' => $f->getId(), 'note_id' => $note->getId()]));
            }
            DB::flush();
        }
    }
}
