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
 * Convert a Form from our declarative to Symfony's representation
 *
 * @package  GNUsocial
 * @category Wrapper
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Core\DB\DB;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form as SymfForm;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface as SymfFormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class converts our own form representation to Symfony's
 *
 * Example:
 * ```
 * $form = Form::create([
 * ['content',     TextareaType::class, ['label' => ' ', 'data' => '', 'attr' => ['placeholder' => _m($placeholder_string[$rand_key])]]],
 * ['attachments', FileType::class,     ['label' => ' ', 'data' => null, 'multiple' => true, 'required' => false]],
 * ['visibility',  ChoiceType::class,   ['label' => _m('Visibility:'), 'expanded' => true, 'data' => 'public', 'choices' => [_m('Public') => 'public', _m('Instance') => 'instance', _m('Private') => 'private']]],
 * ['to',          ChoiceType::class,   ['label' => _m('To:'), 'multiple' => true, 'expanded' => true, 'choices' => $to_tags]],
 * ['post',        SubmitType::class,   ['label' => _m('Post')]],
 * ]);
 * ```
 * turns into
 * ```
 * \Symfony\Component\Form\Form {
 * config: Symfony\Component\Form\FormBuilder { ... }
 * ...
 * children: Symfony\Component\Form\Util\OrderedHashMap {
 * elements: array:5 [
 * "content" => Symfony\Component\Form\Form { ... }
 * "attachments" => Symfony\Component\Form\Form { ... }
 * "visibility" => Symfony\Component\Form\Form { ... }
 * "to" => Symfony\Component\Form\Form { ... }
 * "post" => Symfony\Component\Form\SubmitButton { ... }
 * ]
 * ...
 * }
 * ...
 * }
 * ```
 */
abstract class Form
{
    private static ?FormFactoryInterface $form_factory;

    public static function setFactory($ff): void
    {
        self::$form_factory = $ff;
    }

    /**
     * Create a form with the given associative array $form as fields
     */
    public static function create(
        array $form,
        ?object $target = null,
        array $extra_data = [],
        string $type = 'Symfony\Component\Form\Extension\Core\Type\FormType',
        array $form_options = [],
    ): SymfFormInterface {
        $name = $form[array_key_last($form)][0];
        $fb   = self::$form_factory->createNamedBuilder($name, $type, data: null, options: array_merge($form_options, ['translation_domain' => false]));
        foreach ($form as [$key, $class, $options]) {
            if ($class == SubmitType::class && \in_array($key, ['save', 'publish', 'post'])) {
                Log::critical($m = "It's generally a bad idea to use {$key} as a form name, because it can conflict with other forms in the same page");
                throw new ServerException($m);
            }
            if ($target != null && empty($options['data']) && (mb_strstr($key, 'password') == false) && $class != SubmitType::class) {
                if (isset($extra_data[$key])) {
                    // @codeCoverageIgnoreStart
                    $options['data'] = $extra_data[$key];
                // @codeCoverageIgnoreEnd
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

    /**
     * Whether the given $field of $form has the `required` property
     * set, defaults to true
     */
    public static function isRequired(array $form, string $field): bool
    {
        return $form[$field][2]['required'] ?? true;
    }

    /**
     * Handle the full life cycle of a form. Creates it with @see
     * self::create and inserts the submitted values into the database
     *
     * @throws ServerException
     */
    public static function handle(array $form_definition, Request $request, ?object $target, array $extra_args = [], ?callable $extra_step = null, array $create_args = [], ?SymfForm $testing_only_form = null): mixed
    {
        $form = $testing_only_form ?? self::create($form_definition, $target, ...$create_args);

        $form->handleRequest($request);
        if ($request->getMethod() === 'POST' && $form->isSubmitted()) {
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->all() as $child) {
                    if (!$child->isValid()) {
                        $errors[$child->getName()] = (string) $form[$child->getName()]->getErrors();
                    }
                }
                return $errors;
            } else {
                $data = $form->getData();
                if (\is_null($target)) {
                    return $data;
                }

                unset($data['translation_domain'], $data['save']);
                foreach ($data as $key => $val) {
                    $method = 'set' . ucfirst(Formatting::snakeCaseToCamelCase($key));
                    if (method_exists($target, $method)) {
                        if (isset($extra_args[$key])) {
                            // @codeCoverageIgnoreStart
                            $target->{$method}($val, $extra_args[$key]);
                        // @codeCoverageIgnoreEnd
                        } else {
                            $target->{$method}($val);
                        }
                    }
                }

                if (isset($extra_step)) {
                    // @codeCoverageIgnoreStart
                    $extra_step($data, $extra_args);
                    // @codeCoverageIgnoreEnd
                }

                DB::merge($target);
                DB::flush();
                throw new RedirectException(url: $request->getPathInfo());
            }
        }

        return $form;
    }
}
