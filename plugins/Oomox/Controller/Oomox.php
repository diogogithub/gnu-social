<?php

declare(strict_types=1);

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

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Form;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use Plugin\Oomox\Entity;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function App\Core\I18n\_m;

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
     * @throws NoLoggedInUser
     */
    public static function oomoxSettingsLight(Request $request): array
    {
        $user = Common::ensureLoggedIn();
        $actor_id = $user->getId();

        $current_oomox_settings = \Plugin\Oomox\Oomox::getEntity($user);
        $form_light = (new Oomox)->getOomoxForm($current_oomox_settings, true);

        $form_light->handleRequest($request);
        if ($form_light->isSubmitted() && $form_light->isValid()) {
            $data = $form_light->getData();
            $current_oomox_settings = Entity\Oomox::create(
                [
                    'actor_id' => $actor_id,
                    'colour_foreground_light' => $data['colour_foreground'],
                    'colour_background_hard_light' => $data['colour_background_hard'],
                    'colour_background_card_light' => $data['colour_background_card'],
                    'colour_border_light' => $data['colour_border'],
                    'colour_accent_light' => $data['colour_accent'],
                    'colour_shadow_light' => $data['colour_shadow'],
                ]
            );
            DB::merge($current_oomox_settings);
            DB::flush();

            Cache::delete(\Plugin\Oomox\Oomox::cacheKey($user));

            throw new RedirectException();
        }

        return ['_template' => 'oomox/oomoxSettingsLight.html.twig', 'oomoxLight' => $form_light->createView()];
    }

    public static function oomoxSettingsDark(Request $request): array
    {
        $user = Common::ensureLoggedIn();
        $actor_id = $user->getId();

        $current_oomox_settings = \Plugin\Oomox\Oomox::getEntity($user);
        $form_dark = (new Oomox)->getOomoxForm($current_oomox_settings, false);

        if (is_null($current_oomox_settings)) {
            Entity\Oomox::create([]);
        }

        $form_dark->handleRequest($request);
        if ($form_dark->isSubmitted() && $form_dark->isValid()) {
            $data = $form_dark->getData();
            $current_oomox_settings = Entity\Oomox::create(
                [
                    'actor_id' => $actor_id,
                    'colour_foreground_dark' => $data['colour_foreground'],
                    'colour_background_hard_dark' => $data['colour_background_hard'],
                    'colour_background_card_dark' => $data['colour_background_card'],
                    'colour_border_dark' => $data['colour_border'],
                    'colour_accent_dark' => $data['colour_accent'],
                    'colour_shadow_dark' => $data['colour_shadow'],
                ]
            );
            DB::merge($current_oomox_settings);
            DB::flush();

            Cache::delete(\Plugin\Oomox\Oomox::cacheKey($user));

            throw new RedirectException();
        }

        return ['_template' => 'oomox/oomoxSettingsDark.html.twig', 'oomoxDark' => $form_dark->createView()];
    }


    /**
     * @param Entity\Oomox $current_oomox_settings
     * @return FormInterface
     * @throws ServerException
     */
    public function getOomoxForm(?Entity\Oomox $current_oomox_settings, bool $is_light): FormInterface
    {
        return Form::create([
            ['colour_foreground', ColorType::class, [
                'html5' => true,
                'data' => '',
                'label' => _m('Foreground colour'),
                'help' => _m('Choose the foreground colour'),],
            ],
            ['colour_background_hard', ColorType::class, [
                'html5' => true,
                'data' => '',
                'label' => _m('Background colour'),
                'help' => _m('Choose the background colour'),],
            ],
            ['colour_background_card', ColorType::class, [
                'html5' => true,
                'data' => '',
                'label' => _m('Card background colour'),
                'help' => _m('Choose the card background colour'),],
            ],
            ['colour_border', ColorType::class, [
                'html5' => true,
                'data' => '',
                'label' => _m('Border colour'),
                'help' => _m('Choose colour of borders'),],
            ],
            ['colour_accent', ColorType::class, [
                'html5' => true,
                'data' => '',
                'label' => _m('Accent colour'),
                'help' => _m('Choose the accent colour'),],
            ],
            ['colour_shadow', ColorType::class, [
                'html5' => true,
                'data' => '',
                'label' => _m('Shadow colour'),
                'help' => _m('Choose color of shadows'),],
            ],
            ['hidden', HiddenType::class, []],
            ['save_oomox_colours', SubmitType::class, ['label' => _m('Submit')]],
        ]);
    }

    /**
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws ServerException
     */
    public function oomoxCSS()
    {
        $user = Common::ensureLoggedIn();

        $oomox_table = \Plugin\Oomox\Oomox::getEntity($user);
        if (is_null($oomox_table)) {
            throw new ClientException(_m('No custom colors defined', 404));
        }

        $content = Formatting::twigRenderFile('/oomox/root_override.css.twig', ['oomox' => $oomox_table]);
        return new Response($content, status: 200, headers: ['content-type' => 'text/css']);
    }
}
