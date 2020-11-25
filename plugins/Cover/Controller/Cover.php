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

namespace Plugin\Cover\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\Cover as CoverEntity;
use App\Util\Common;
use App\Util\Exception\ClientException;
use Component\Media\Media;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Cover
{
    /**
     * Display and handle the cover edit page
     */
    public function cover(Request $request)
    {
        $form = Form::create([
            ['cover', FileType::class,   ['label' => _m('Cover'), 'help' => _m('You can upload your personal cover. The maximum file size is 2MB.')]],
            ['hidden', HiddenType::class, []],
            ['save',   SubmitType::class, ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (isset($data['cover'])) {
                $sfile = $data['cover'];
            } else {
                throw new ClientException('Invalid form');
            }
            $user     = Common::user();
            $actor_id = $user->getId();
            $file     = Media::validateAndStoreFile($sfile, Common::config('cover', 'dir'), $title = null, $is_local = true, $use_unique = $actor_id);
            $old_file = null;
            $cover    = DB::find('cover', ['gsactor_id' => $actor_id]);
            // Must get old id before inserting another one
            if ($cover != null) {
                //$old_file = $avatar->delete();
            }
            DB::persist($file);
            // Can only get new id after inserting
            DB::flush();
            $cover = CoverEntity::create(['gsactor_id' => $actor_id, 'file_id' => $file->getId()]);
            //var_dump($cover);
            DB::persist($cover);
            DB::flush();
            // Only delete files if the commit went through
            if ($old_file != null) {
                @unlink($old_file);
            }
        }

        return ['_template' => 'cover/cover.html.twig', 'form' => $form->createView()];
    }
}
