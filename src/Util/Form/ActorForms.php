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
 * Transform between string and list of typed profiles
 *
 * @package  GNUsocial
 * @category Form
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util\Form;

use App\Core\Cache;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\ServerException;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class ActorForms
{
    /**
     * Actor personal information panel
     *
     * @throws NicknameEmptyException
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     * @throws NoLoggedInUser
     * @throws ServerException
     */
    public static function personalInfo(Request $request, Actor $target, ?LocalUser $user = null): mixed
    {
        // Defining the various form fields
        $form_definition = [
            ['nickname', TextType::class, ['label' => _m('Nickname'), 'required' => true, 'help' => _m('1-64 lowercase letters or numbers, no punctuation or spaces.')]],
            ['full_name', TextType::class, ['label' => _m('Full Name'), 'required' => false, 'help' => _m('A full name is required, if empty it will be set to your nickname.')]],
            ['homepage', TextType::class, ['label' => _m('Homepage'), 'required' => false, 'help' => _m('URL of your homepage, blog, or profile on another site.')]],
            ['bio', TextareaType::class, ['label' => _m('Bio'), 'required' => false, 'help' => _m('Describe yourself and your interests.')]],
            ['phone_number', PhoneNumberType::class, ['label' => _m('Phone number'), 'required' => false, 'help' => _m('Your phone number'), 'data_class' => null]],
            ['location', TextType::class, ['label' => _m('Location'), 'required' => false, 'help' => _m('Where you are, like "City, State (or Region), Country".')]],
            ['save_personal_info', SubmitType::class, ['label' => _m('Save personal info')]],
        ];

        // Setting nickname normalised and setting actor cache
        $extra_step = function ($data, $extra_args) use ($user, $target) {
            if (!\is_null($user)) {
                $user->setNicknameSanitizedAndCached($data['nickname']);
            }

            $cache_keys = Actor::cacheKeys($target->getId());
            foreach (['id', 'nickname', 'fullname'] as $key) {
                Cache::delete($cache_keys[$key]);
            }
        };

        return Form::handle(
            $form_definition,
            $request,
            target: $target,
            extra_args: [],
            extra_step: $extra_step,
        );
    }
}
