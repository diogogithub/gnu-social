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

namespace Plugin\ProfileColor\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\ProfileColor as PColor;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\ServerException;
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
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ProfileColor
{
    /**
     * Add/change cover
     *
     * @param Request $request
     *
     * @throws ClientException Invalid form
     * @throws ServerException Invalid file type
     *
     * @return array template
     */
    public function profileColorSettings(Request $request)
    {
        $user     = Common::user();
        $actor_id = $user->getId();
        $pcolor   = DB::find('profile_color', ['gsactor_id' => $actor_id]);
        $color    = '#000000';
        if ($pcolor != null) {
            $color = $pcolor->getColor();
        }

        //print_r("STORED: " );
        //var_dump($color);

        $form = Form::create([
            ['color',   ColorType::class,   ['data' => $color, 'label' => _m('Profile Color')]],
            ['hidden', HiddenType::class, []],
            ['save',   SubmitType::class, ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            //var_dump($data['color']);

            // Must get old id before inserting another one
            if ($pcolor != null) {
                DB::remove($pcolor);
                DB::flush();
            }

            $pcolor = PColor::create(['gsactor_id' => $actor_id, 'color' => $data['color']]);
            DB::persist($pcolor);
            DB::flush();
        }

        return ['_template' => 'profilecolor/profilecolor.html.twig', 'form' => $form->createView()];
    }
}
