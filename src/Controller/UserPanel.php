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
 * @author    Eliseu Amaro <eliseu@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

// {{{ Imports

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Form\ArrayTransformer;
use Doctrine\DBAL\Types\Types;
use Exception;
use Functional as F;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

// }}} Imports

class UserPanel extends AbstractController
{
    public function personal_info(Request $request)
    {
        $user            = Common::user();
        $profile         = $user->getProfile();
        $extra           = ['self_tags' => $profile->getSelfTags()];
        $form_definition = [
            ['nickname',  TextType::class,     ['label' => _m('Nickname'),  'required' => true,  'help' => _m('1-64 lowercase letters or numbers, no punctuation or spaces.')]],
            ['full_name', TextType::class,     ['label' => _m('Full Name'), 'required' => false, 'help' => _m('A full name is required, if empty it will be set to your nickname.')]],
            ['homepage',  TextType::class,     ['label' => _m('Homepage'),  'required' => false, 'help' => _m('URL of your homepage, blog, or profile on another site.')]],
            ['bio',       TextareaType::class, ['label' => _m('Bio'),       'required' => false, 'help' => _m('Describe yourself and your interests.')]],
            ['location',  TextType::class,     ['label' => _m('Location'),  'required' => false, 'help' => _m('Where you are, like "City, State (or Region), Country".')]],
            ['self_tags', TextType::class,     ['label' => _m('Self Tags'), 'required' => false, 'transformer' => ArrayTransformer::class, 'help' => _m('Tags for yourself (letters, numbers, -, ., and _), comma- or space-separated.')]],
            ['save',      SubmitType::class,   ['label' => _m('Save')]],
        ];
        $extra_step = function ($data, $extra_args) use ($user) { $user->setNickname($data['nickname']); };
        $form = Form::handle($form_definition, $request, $profile, $extra, $extra_step, [['self_tags' => $extra['self_tags']]]);

        return ['_template' => 'settings/profile.html.twig', 'prof' => $form->createView()];
    }

    public function account(Request $request)
    {
        $user            = Common::user();
        $form_definition = [
            ['outgoing_email',  TextType::class,        ['label' => _m('Outgoing email'), 'required' => true,  'help' => _m('Change the email we use to contact you')]],
            ['incoming_email',  TextType::class,        ['label' => _m('Incoming email'), 'required' => true,  'help' => _m('Change the email you use to contact us (for posting, for instance)')]],
            ['password',        TextType::class,        ['label' => _m('Password'),       'required' => false, 'help' => _m('Change your password'), 'attr' => ['placeholder' => '********']]],
            ['old_password',    TextType::class,        ['label' => _m('Old password'),   'required' => false, 'help' => _m('Enter your old password for verification'), 'attr' => ['placeholder' => '********']]],
            ['language',        LanguageType::class,    ['label' => _m('Language'),       'required' => false, 'help' => _m('Your preferred language')]],
            ['phone_number',    PhoneNumberType::class, ['label' => _m('Phone number'),   'required' => false, 'help' => _m('Your phone number')]],
            ['save',            SubmitType::class,      ['label' => _m('Save')]],
        ];

        $form = Form::handle($form_definition, $request, $user);

        return ['_template' => 'settings/account.html.twig', 'acc' => $form->createView()];
    }

    public function avatar(Request $request)
    {
        $avatar = Form::create([
            ['avatar',   FileType::class,   ['label' => _m('Avatar'), 'help' => _m('You can upload your personal avatar. The maximum file size is 10MB.')]],
            ['save',     SubmitType::class, ['label' => _m('Submit')]],
        ]);

        return ['_template' => 'settings/avatar.html.twig', 'avatar' => $avatar->createView()];
    }

    public function notifications(Request $request)
    {
        $schema    = DB::getConnection()->getSchemaManager();
        $platform  = $schema->getDatabasePlatform();
        $columns   = Common::array_remove_keys($schema->listTableColumns('user_notification_prefs'), ['user_id', 'transport', 'created', 'modified']);
        $form_defs = ['placeholder' => []];
        foreach ($columns as $name => $col) {
            $val = $col->getType()->convertToPHPValue($col->getDefault(), $platform);
            switch ($col->getType()) {
            case Types::BOOLEAN:
                $form_defs['placeholder'][] = [$name, CheckboxType::class, ['data' => $val, 'label' => _m($col->getComment())]];
                break;
            case Types::INTEGER:
                if ($name == 'target_profile_id') {
                    $form_defs['placeholder'][] = ['target_profiles', TextType::class, ['data' => $val, 'label' => _m($col->getComment())], 'transformer' => ProfileArrayTransformer::class];
                    break;
                }
                // fallthrough
                // no break
            default:
                throw new Exception("Structure of table user_notification_prefs changed in a way not accounted to in notification settings ({$name})", 500);
            }
        }

        Event::handle('AddNotificationTransport', [&$form_defs]);
        unset($form_defs['placeholder']);

        $tabbed_forms = [];
        foreach ($form_defs as $transport_name => $f) {
            $tabbed_forms[$transport_name] = Form::create($f);
        }

        $tabbed_forms = F\map($tabbed_forms, function ($f) { return $f->createView(); });
        return ['_template' => 'settings/notifications.html.twig', 'tabbed_forms' => $tabbed_forms];
    }
}
