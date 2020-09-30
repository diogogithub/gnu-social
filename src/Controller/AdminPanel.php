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
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exceptiion\InvalidFormException;
use App\Util\Formatting;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class AdminPanel extends Controller
{
    public function site(Request $request)
    {
        $defaults = Common::getConfigDefaults();
        $options  = [];
        foreach ($defaults as $key => $inner) {
            $options[$key] = [];
            foreach (array_keys($inner) as $inner_key) {
                $options[_m($key)][_m($inner_key)] = "{$key}:{$inner_key}";
            }
        }

        $form = Form::create([
            ['setting', ChoiceType::class, ['label' => _m('Setting'), 'choices' => $options]],
            ['value',   TextType::class,   ['label' => _m('Value')]],
            ['save',    SubmitType::class, ['label' => _m('Set site setting')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid() && array_key_exists('setting', $data)) {
                list($section, $setting) = explode(':', $data['setting']);
                $value                   = $data['value'];
                if (preg_match('/^[0-9]+$/', $value)) {
                    $value = (int) $value;
                } elseif (strstr($value, ',') === false) {
                    // empty, string
                } elseif (Formatting::toArray($value, $value)) {
                    // empty
                } elseif (preg_match('/true|false/i', $value)) {
                    $value = ($value == 'true');
                }

                $default = $defaults[$section][$setting];
                // Sanity check
                if (gettype($default) === gettype($value)) {
                    $old_value = Common::config($section, $setting);
                    Common::setConfig($section, $setting, $value);
                    return [
                        '_template' => 'config/admin.html.twig',
                        'form'      => $form->createView(),
                        'old_value' => Formatting::toString($old_value),
                        'default'   => Formatting::toString($default),
                    ];
                }
            } else {
                throw new InvalidFormException();
            }
        }

        return [
            '_template' => 'config/admin.html.twig',
            'form'      => $form->createView(),
        ];
    }
}
