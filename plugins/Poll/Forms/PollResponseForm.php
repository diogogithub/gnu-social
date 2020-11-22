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
use App\Entity\Poll;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form as SymfForm;

/**
 * Form to respond a Poll
 *
 * @package  GNUsocial
 * @category PollPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PollResponseForm extends Form
{
    /**
     * Creates a radio form with the options given
     *
     * @param Poll $poll
     * @param int  $noteId
     *
     * @return SymfForm
     */
    public static function make(Poll $poll,int $noteId): SymfForm
    {
        $opts        = $poll->getOptionsArr();
        $formOptions = [];
        $options     = [];
        for ($i = 1; $i <= count($opts); ++$i) {
            $options[$opts[$i - 1]] = $i;
        }
        $formOptions = [
            ['Options' . $poll->getId(), ChoiceType::class, [
                'choices'  => $options,
                'expanded' => true,
            ]],
            ['note_id',     HiddenType::class, ['data' => $noteId]],
            ['pollresponse', SubmitType::class, ['label' => _m('Vote')]],
        ];
        return parent::create($formOptions);
    }
}
