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

/**
 * Handle network public feed
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

use App\Core\Controller;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\InvalidFormException;
use App\Util\Formatting;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class AdminPanel extends Controller
{
    /**
     * Handler for the site admin panel section. Allows the
     * administrator to change various configuration options
     */
    public function site(Request $request)
    {
        // TODO CHECK PERMISSION
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
            ['save_admin',    SubmitType::class, ['label' => _m('Set site setting')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid() && \array_key_exists('setting', $data)) {
                [$section, $setting] = explode(':', $data['setting']);
                if (!isset($defaults[$section]) && !isset($defaults[$section][$setting])) {
                    // @codeCoverageIgnoreStart
                    throw new ClientException(_m('The supplied field doesn\'t exist'));
                    // @codeCoverageIgnoreEnd
                }

                $value = null;
                foreach ([
                    'int' => \FILTER_VALIDATE_INT,
                    'bool' => \FILTER_VALIDATE_BOOL,
                    'string' => [fn ($v) => mb_strstr($v, ',') === false, fn ($v) => $v],
                    'array' => [fn ($v) => mb_strstr($v, ',') !== false, function ($v) { Formatting::toArray($v, $v); return $v; }],
                ] as $type => $validator) {
                    if (!\is_array($validator)) {
                        $value = filter_var($data['value'], $validator, \FILTER_NULL_ON_FAILURE);
                        if ($value !== null) {
                            break;
                        }
                    } else {
                        [$check, $convert] = $validator;
                        if ($check($data['value'])) {
                            $value = $convert($data['value']);
                        }
                    }
                }

                $default = $defaults[$section][$setting];

                // Sanity check
                if (\gettype($default) === \gettype($value)) {
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
                // @codeCoverageIgnoreStart
                throw new InvalidFormException();
                // @codeCoverageIgnoreEnd
            }
        }

        return [
            '_template' => 'config/admin.html.twig',
            'form'      => $form->createView(),
        ];
    }
}
