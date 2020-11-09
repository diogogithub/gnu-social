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

namespace Plugin\Poll\Forms;

use App\Core\Form;
use function App\Core\I18n\_m;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form as SymfForm;

/**
 * Form to add a Poll
 *
 * @package  GNUsocial
 * @category PollPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NewPollForm extends Form
{
    /**
     * Creates a form with variable num of fields
     *
     * @param int $optionNum
     *
     * @return SymfForm
     */
    public static function make(int $optionNum): SymfForm
    {
        $options    = [];
        $options[0] = ['Question', TextType::class, ['label' => _m(('Question'))]];
        $i          = 1;
        for ($i; $i <= $optionNum; ++$i) {
            //['Option_i',   TextType::class,   ['label' => _m('Option i')]],
            $options[$i] = ['Option_' . $i, TextType::class, ['label' => _m(('Option ' . $i))]];
        }
        $options[$i + 1] = ['save', SubmitType::class, ['label' => _m('Submit Poll')]];

        return parent::create($options);
    }
}
