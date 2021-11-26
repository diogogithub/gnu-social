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

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Util\Exception\ClientException;
use App\Util\Exception\NotFoundException;
use App\Util\Formatting;
use http\Client\Curl\User;
use Symfony\Component\HttpFoundation\Response;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Plugin\Oomox\Entity;
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
     * @throws \App\Util\Exception\NoLoggedInUser
     */
    public static function oomoxSettings(Request $request): array
    {
        $user    = Common::ensureLoggedIn();
        $actor_id = $user->getId();

        $current_oomox_settings = DB::find('profile_color', ['actor_id' => $actor_id]);

        $form = Form::create([
            ['colour_foreground',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings?->getColourForeground(),
                'label' => _m('Foreground colour'),
                'help'  => _m('Choose the foreground colour'), ],
            ],
            ['colour_background_hard',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings?->getColourBackgroundHard(),
                'label' => _m('Background colour'),
                'help'  => _m('Choose the background colour'), ],
            ],
            ['colour_background_card',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings?->getColourBackgroundCard(),
                'label' => _m('Card background colour'),
                'help'  => _m('Choose the card background colour'), ],
            ],
            ['colour_border',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings?->getColourBorder(),
                'label' => _m('Border colour'),
                'help'  => _m('Choose colour of borders'), ],
            ],
            ['colour_accent',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings?->getColourAccent(),
                'label' => _m('Accent colour'),
                'help'  => _m('Choose the accent colour'), ],
            ],
            ['colour_shadow',   ColorType::class, [
                'html5' => true,
                'data'  => $current_oomox_settings?->getColourShadow(),
                'label' => _m('Shadow colour'),
                'help'  => _m('Choose color of shadows'), ],
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
            DB::merge($current_oomox_settings);
            DB::flush();

            Cache::set(\Plugin\Oomox\Oomox::cacheKey($user), $current_oomox_settings);

            throw new RedirectException();
        }

        return ['_template' => 'oomox/oomoxSettings.html.twig', 'oomox' => $form->createView()];
    }

    public function oomoxCSS() {
        $user = Common::ensureLoggedIn();
        $actor_id = $user->getId();

        try {
            $oomox_table = Cache::get("oomox-css-{$actor_id}", fn() => DB::findOneBy('oomox', ['actor_id' => $actor_id]));
        } catch (NotFoundException $e) {
            throw new ClientException(_m('No custom colours defined.'),404, $e);
        }

        $content = Formatting::twigRenderFile('/oomox/root_override.css.twig', ['oomox' => $oomox_table]);
        return new Response($content, status: 200, headers: ['content-type' => 'text/css']);
    }
}
