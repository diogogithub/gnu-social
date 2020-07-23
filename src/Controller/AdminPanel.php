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

/**
 * Handle network public feed
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\DB\DefaultSettings;
use App\Core\Form;
use function App\Core\I18n\_m;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class AdminPanel extends Controller
{
    public function site(Request $request)
    {
        $defaults = DefaultSettings::$defaults;
        $options  = [];
        foreach ($defaults as $key => $inner) {
            $options[$key] = [];
            foreach (array_keys($inner) as $inner_key) {
                $options[_m($key)][_m($inner_key)] = "{$key}:{$inner_key}";
            }
        }

        $form = Form::create([
            [_m('Setting'), ChoiceType::class, ['choices' => $options]],
            [_m('Value'),   TextType::class],
            ['save',        SubmitType::class, ['label' => _m('Set site setting')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid() && array_key_exists(_m('Setting'), $data)) {
                list($section, $setting) = explode(':', $data[_m('Setting')]);
                $value                   = $data[_m('Value')];
                $default                 = $defaults[$section][$setting];
                // Sanity check
                if (gettype($default) === gettype($value)) {
                    $conf      = DB::find('config', ['section' => $section, 'setting' => $setting]);
                    $old_value = unserialize($conf->getValue());
                    $conf->setValue(serialize($value));
                    DB::flush();
                    return [
                        '_template' => 'config/admin.html.twig',
                        'form'      => $form->createView(),
                        'old_value' => $old_value,
                        'default'   => $default,
                    ];
                }
            } else {
                // TODO Display error
            }
        }

        return [
            '_template' => 'config/admin.html.twig',
            'form'      => $form->createView(),
        ];
    }
}
