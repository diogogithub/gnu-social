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
 * @category Wrapper
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Core\DB\DB;
use App\Util\Formatting;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form as SymfForm;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class Form
{
    private static ?FormFactoryInterface $form_factory;

    public static function setFactory($ff): void
    {
        self::$form_factory = $ff;
    }

    public static function create(array $form,
                                  ?object $target = null,
                                  array $extra_data = [],
                                  string $type = 'Symfony\Component\Form\Extension\Core\Type\FormType',
                                  array $builder_options = []): SymfForm
    {
        $name = $form[array_key_last($form)][0];
        $fb   = self::$form_factory->createNamedBuilder($name, $type, array_merge($builder_options, ['translation_domain' => false]));
        foreach ($form as list($key, $class, $options)) {
            if ($target != null && empty($options['data']) && (strstr($key, 'password') == false) && $class != SubmitType::class) {
                if (isset($extra_data[$key])) {
                    $options['data'] = $extra_data[$key];
                } else {
                    $method = 'get' . ucfirst(Formatting::snakeCaseToCamelCase($key));
                    if (method_exists($target, $method)) {
                        $options['data'] = $target->{$method}();
                    }
                }
            }
            unset($options['hide']);
            if (isset($options['transformer'])) {
                $transformer = $options['transformer'];
                unset($options['transformer']);
            }
            $fb->add($key, $class, $options);
            if (isset($transformer)) {
                $fb->get($key)->addModelTransformer(new $transformer());
                unset($transformer);
            }
        }
        return $fb->getForm();
    }

    public static function isRequired(array $form, string $field): bool
    {
        return $form[$field][2]['required'] ?? true;
    }

    public static function handle(array $form_definition, Request $request, object $target, array $extra_args = [], ?callable $extra_step = null, array $create_args = [])
    {
        $form = self::create($form_definition, $target, ...$create_args);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($target == null) {
                return $data;
            }
            unset($data['translation_domain'], $data['save']);
            foreach ($data as $key => $val) {
                $method = 'set' . ucfirst(Formatting::snakeCaseToCamelCase($key));
                if (method_exists($target, $method)) {
                    if (isset($extra_args[$key])) {
                        $target->{$method}($val, $extra_args[$key]);
                    } else {
                        $target->{$method}($val);
                    }
                }
            }
            if (isset($extra_step)) {
                $extra_step($data, $extra_args);
            }
            DB::flush();
        }
        return $form;
    }
}
