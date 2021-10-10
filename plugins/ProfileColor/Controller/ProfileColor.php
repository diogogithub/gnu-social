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

namespace Plugin\ProfileColor\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Plugin\ProfileColor\Entity;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Profile Color controller
 *
 * @package  GNUsocial
 * @category ProfileColor
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @author    Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ProfileColor
{
    /**
     * Change Profile color background
     *
     * @throws RedirectException
     * @throws ServerException
     */
    public static function profileColorSettings(Request $request): array
    {
        $actor    = Common::actor();
        $actor_id = $actor->getId();

        $current_profile_color = DB::find('profile_color', ['actor_id' => $actor_id]);

        $form = Form::create([
            ['background_color',   ColorType::class, [
                'html5' => true,
                'data'  => $current_profile_color ? $current_profile_color->getBackground() : '#000000',
                'label' => _m('Profile background color'),
                'help'  => _m('Choose your Profile background color'), ],
            ],
            ['foreground_color',   ColorType::class, [
                'html5' => true,
                'data'  => $current_profile_color ? $current_profile_color->getColor() : '#000000',
                'label' => _m('Profile foreground color'),
                'help'  => _m('Choose your Profile foreground color'), ],
            ],
            ['hidden', HiddenType::class, []],
            ['save_profile_color',   SubmitType::class, ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($current_profile_color !== null) {
                DB::remove($current_profile_color);
                DB::flush();
            }

            $data                  = $form->getData();
            $current_profile_color = Entity\ProfileColor::create(['actor_id' => $actor_id, 'color' => $data['foreground_color'], 'background' => $data['background_color']]);
            DB::persist($current_profile_color);
            DB::flush();

            throw new RedirectException();
        }

        return ['_template' => 'profileColor/profileColorSettings.html.twig', 'profile_color' => $form->createView()];
    }
}
