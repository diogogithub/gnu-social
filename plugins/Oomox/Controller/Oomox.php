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

namespace Plugin\Oomox\Controller;

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
 * Oomox controller
 *
 * @package  GNUsocial
 * @category Oomox
 *
 * @author    Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Oomox
{
    /**
     * Change theme colours
     *
     * @throws RedirectException
     * @throws ServerException
     */
    public static function oomoxSettings(Request $request): array
    {
        $actor    = Common::actor();
        $actor_id = $actor->getId();

        $current_oomox_settings = DB::find('profile_color', ['actor_id' => $actor_id]);

        $form = Form::create([
            ['colour_foreground',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings ? $current_oomox_settings->getColourForeground() : '#09090d',
                'label' => _m('Profile background color'),
                'help'  => _m('Choose your Profile background color'), ],
            ],
            ['colour_background_hard',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings ? $current_oomox_settings->getColourBackgroundHard() : '#ebebeb',
                'label' => _m('Profile foreground color'),
                'help'  => _m('Choose your Profile foreground color'), ],
            ],
            ['colour_background_card',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings ? $current_oomox_settings->getColourBackgroundCard() : '#f0f0f0',
                'label' => _m('Profile background color'),
                'help'  => _m('Choose your Profile background color'), ],
            ],
            ['colour_border',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings ? $current_oomox_settings->getColourBorder() : '#d5d5d5',
                'label' => _m('Profile foreground color'),
                'help'  => _m('Choose your Profile foreground color'), ],
            ],
            ['colour_accent',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings ? $current_oomox_settings->getColourAccent() : '#a22430',
                'label' => _m('Profile foreground color'),
                'help'  => _m('Choose your Profile foreground color'), ],
            ],
            ['colour_shadow',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings ? $current_oomox_settings->getColourShadow() : '#24243416',
                'label' => _m('Profile foreground color'),
                'help'  => _m('Choose your Profile foreground color'), ],
            ],
            ['hidden', HiddenType::class, []],
            ['save_oomox_colours',   SubmitType::class, ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($current_oomox_settings !== null) {
                DB::remove($current_oomox_settings);
                DB::flush();
            }

            $data = $form->getData();
            $current_oomox_settings = Entity\Oomox::create(
                [
                    'actor_id' => $actor_id,
                    'colour_foreground' => $data['colour_foreground'],
                    'colour_background_hard' => $data['colour_background_hard'],
                    'colour_background_card' => $data['colour_background_card'],
                    'colour_border' => $data['colour_border'],
                    'colour_accent' => $data['colour_accent'],
                    'colour_shadow' => $data['colour_shadow'],
                ]
            );
            DB::persist($current_oomox_settings);
            DB::flush();

            throw new RedirectException();
        }

        return ['_template' => 'oomox/oomoxSettings.html.twig', 'oomox' => $form->createView()];
    }
}
