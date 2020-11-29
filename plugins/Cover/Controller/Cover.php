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
use App\Util\Exception\ServerException;
use Component\Media\Media;
use Component\Media\Media as M;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\File as F;

/**
 * Cover controller
 *
 * @package  GNUsocial
 * @category CoverPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Cover
{
    /**
     * Display and handle the cover edit page, where a user can add or
     * edit their cover image
     *
     * @param Request $request
     *
     * @throws ClientException Invalid form
     * @throws ServerException Invalid file type
     *
     * @return array template
     */
    public function coverSettings(Request $request)
    {
        $form = Form::create([
            ['cover', FileType::class,   ['label' => _m('Cover'), 'help' => _m('You can upload your personal cover. The maximum file size is 2MB.'),
                'constraints'                     => [
                    new F([
                        'maxSize'   => '2048k',
                        'mimeTypes' => [
                            'image/gif',
                            'image/png',
                            'image/jpeg',
                            'image/bmp',
                            'image/webp',
                        ],
                        'maxSizeMessage'   => 'Image exceeded maximum size',
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ]), ], ]],
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

            if (explode('/',$sfile->getMimeType())[0] != 'image') {
                throw new ServerException('Invalid file type');
            }
            $user     = Common::user();
            $actor_id = $user->getId();
            $file     = Media::validateAndStoreFile($sfile, Common::config('cover', 'dir'), $title = null, $is_local = true, $use_unique = $actor_id);
            $old_file = null;
            $cover    = DB::find('cover', ['gsactor_id' => $actor_id]);
            // Must get old id before inserting another one
            if ($cover != null) {
                $old_file = $cover->delete();
                DB::remove($cover);
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

    /**
     * get user cover
     *
     * @return mixed cover file
     */
    public function cover()
    {
        $cover = DB::find('cover', ['gsactor_id' => Common::user()->getId()]);
        $file  = $cover->getFile();
        return M::sendFile($cover->getFilePath(), $file->getMimetype(), $file->getTitle());
    }
}
