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

namespace Plugin\PollPlugin\Forms;

use App\Core\Form;
use function App\Core\I18n\_m;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form as SymfForm;

const MAX_OPT = 5;
class NewPollForm extends Form
{
    public static function make(int $optionNum): SymfForm
    {
        $optionNum = min(MAX_OPT,$optionNum);
        $options   = [];
        array_push($options,['Question', TextType::class, ['label' => _m(('Question'))]]);
        for ($i = 1; $i <= $optionNum; ++$i) {
            //['Option_i',   TextType::class,   ['label' => _m('Option i')]],
            array_push($options,['Option_' . $i, TextType::class, ['label' => _m(('Option ' . $i))]]);
        }
        array_push($options, ['save', SubmitType::class, ['label' => _m('Submit Poll')]]);

        return parent::create($options);
    }
}
