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
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Plugin\Cover\Entity\Cover as CoverEntity;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public static function coverSettings(Request $request)
    {
        $user     = Common::user();
        $actor_id = $user->getId();

        $form = Form::create([
            ['cover', FileType::class, ['label' => _m('Cover'), 'help' => _m('You can upload your personal cover. The maximum file size is 2MB.'),
                'constraints'                   => [
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
            ['save_color',   SubmitType::class, ['label' => _m('Submit')]],
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
            $file     = GSFile::storeFileAsAttachment($sfile);
            $old_file = null;
            $cover    = DB::find('cover', ['gctor_id' => $actor_id]);
            // Must get old id before inserting another one
            if ($cover != null) {
                $old_file = $cover->delete();
                DB::remove($cover);
            }
            DB::persist($file);
            // Can only get new id after inserting
            DB::flush();
            $cover = CoverEntity::create(['actor_id' => $actor_id, 'file_id' => $file->getId()]);
            DB::persist($cover);
            DB::flush();
            // Only delete files if the commit went through
            if ($old_file != null) {
                @unlink($old_file);
            }
            throw new RedirectException();
        }

        $removeForm = null;
        $cover      = DB::find('cover', ['actor_id' => $actor_id]);
        if ($cover != null) {
            $form2 = Form::create([
                ['remove',   SubmitType::class, ['label' => _m('Remove')]],
            ]);
            $form2->handleRequest($request);
            if ($form2->isSubmitted() && $form2->isValid()) {
                $old_file = $cover->delete();
                DB::remove($cover);
                DB::flush();
                @unlink($old_file);
                throw new RedirectException();
            }
            $removeForm = $form2->createView();
        }
        return ['_template' => 'cover/cover.html.twig', 'cover' => $form->createView(), 'cover_remove_form' => $removeForm];
    }

    /**
     * get user cover
     *
     * @return mixed cover file
     */
    public function cover()
    {
        // $cover = DB::find('cover', ['actor_id' => Common::user()->getId()]);
        // if ($cover == null) {
        //     return  new Response('Cover not found',Response::HTTP_NOT_FOUND);
        // }
        // $file = $cover->getFile();
        // if ($file == null) {
        //     return  new Response('Cover File not found',Response::HTTP_NOT_FOUND);
        // }
        // return GSFile::sendFile($cover->getFilePath(), $file->getMimetype(), $file->getTitle());
    }
}
