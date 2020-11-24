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

namespace Plugin\Cover\Controller;

use App\Core\Form;
use function App\Core\I18n\_m;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Cover
{
    public function cover(Request $request)
    {
        $form = Form::create([
            ['cover', FileType::class,   ['label' => _m('Cover'), 'help' => _m('You can upload your personal cover. The maximum file size is 2MB.')]],
            ['hidden', HiddenType::class, []],
            ['save',   SubmitType::class, ['label' => _m('Submit')]],
        ]);
        return ['_template' => 'Cover/cover.html.twig', 'form' => $form->createView()];
    }
}
