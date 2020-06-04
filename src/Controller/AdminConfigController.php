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

// use App\Core\GSEvent as Event;
// use App\Util\Common;
use App\Core\DefaultSettings;
use App\Core\I18n;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class AdminConfigController extends AbstractController
{
    public function __invoke(Request $request)
    {
        $options = [];
        foreach (DefaultSettings::$defaults as $key => $inner) {
            $options[$key] = [];
            foreach (array_keys($inner) as $inner_key) {
                $options[I18n::_m($key)][I18n::_m($inner_key)] = "{$key}:{$inner_key}";
            }
        }

        $form = $this->createFormBuilder()
                     ->add(I18n::_m('Setting'), ChoiceType::class, ['choices' => $options])
                     ->add(I18n::_m('Value'),   TextType::class)
                     ->add('save',    SubmitType::class, ['label' => I18n::_m('Set site setting')])
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            var_dump($data);

            // Stay in this page
            return $this->redirect($request->getUri());
        }

        return $this->render('config/admin.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
