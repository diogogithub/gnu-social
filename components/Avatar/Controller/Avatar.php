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

namespace Component\Avatar\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Core\GSFile;
use App\Core\GSFile as M;
use function App\Core\I18n\_m;
use App\Entity\Avatar as AvatarEntity;
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use App\Util\TemporaryFile;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\Request;

class Avatar extends Controller
{
    /**
     * @throws Exception
     */
    public function avatar_view(Request $request, int $gsactor_id, string $size)
    {
        switch ($size) {
            case 'full':
                $res = \Component\Avatar\Avatar::getAvatarFileInfo($gsactor_id);
                return M::sendFile($res['file_path'], $res['mimetype'], $res['title']);
            default:
                throw new Exception('Not implemented');
        }
    }

    /**
     * Local user avatar panel
     */
    public function settings_avatar(Request $request)
    {
        $form = Form::create([
            ['avatar', FileType::class,     ['label' => _m('Avatar'), 'help' => _m('You can upload your personal avatar. The maximum file size is 2MB.'), 'multiple' => false, 'required' => false]],
            ['remove', CheckboxType::class, ['label' => _m('Remove avatar'), 'help' => _m('Remove your avatar and use the default one'), 'required' => false, 'value' => false]],
            ['hidden', HiddenType::class,   []],
            ['save',   SubmitType::class,   ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data       = $form->getData();
            $user       = Common::user();
            $gsactor_id = $user->getId();
            if ($data['remove'] == true) {
                try {
                    $avatar = DB::findOneBy('avatar', ['gsactor_id' => $gsactor_id]);
                    $avatar->delete();
                    Event::handle('DeleteCachedAvatar', [$user->getId()]);
                } catch (NotFoundException) {
                    $form->addError(new FormError(_m('No avatar set, so cannot delete')));
                }
            } else {
                $sfile = null;
                if (isset($data['hidden'])) {
                    // Cropped client side
                    $matches = [];
                    if (!empty(preg_match('/data:([^;]*)(;(base64))?,(.*)/', $data['hidden'], $matches))) {
                        list(, $mimetype_user, , $encoding_user, $data_user) = $matches;
                        if ($encoding_user == 'base64') {
                            $data_user = base64_decode($data_user);
                            $tempfile  = new TemporaryFile('avatar');
                            $path      = $tempfile->getPath();
                            file_put_contents($path, $data_user);
                            $sfile = new SymfonyFile($path);
                        } else {
                            Log::info('Avatar upload got an invalid encoding, something\'s fishy and/or wrong');
                        }
                    }
                } elseif (isset($data['avatar'])) {
                    // Cropping failed (e.g. disabled js), have file as uploaded
                    $sfile = $data['avatar'];
                } else {
                    throw new ClientException('Invalid form');
                }
                $attachment     = GSFile::validateAndStoreAttachment($sfile, Common::config('avatar', 'dir'), $title = null, $is_local = true, $use_unique = $gsactor_id);
                $old_attachment = null;
                $avatar         = DB::find('avatar', ['gsactor_id' => $gsactor_id]);
                // Must get old id before inserting another one
                if ($avatar != null) {
                    $old_attachment = $avatar->delete();
                }
                DB::persist($attachment);
                // Can only get new id after inserting
                DB::flush();
                DB::persist(AvatarEntity::create(['gsactor_id' => $gsactor_id, 'attachment_id' => $attachment->getId()]));
                DB::flush();
                // Only delete files if the commit went through
                if ($old_attachment != null) {
                    @unlink($old_attachment);
                }
                Event::handle('DeleteCachedAvatar', [$user->getId()]);
            }
        }

        return ['_template' => 'settings/avatar.html.twig', 'avatar' => $form->createView()];
    }
}
