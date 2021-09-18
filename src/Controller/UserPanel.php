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
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Eliseu Amaro <eliseu@fc.up.pt>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

// {{{ Imports

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\UserNotificationPrefs;
use App\Util\Common;
use App\Util\Exception\AuthenticationException;
use App\Util\Exception\ServerException;
use App\Util\Form\ActorArrayTransformer;
use App\Util\Form\ArrayTransformer;
use App\Util\Form\FormFields;
use App\Util\Formatting;
use Doctrine\DBAL\Types\Types;
use Exception;
use Functional as F;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

// }}} Imports

class UserPanel extends AbstractController
{
    /**
     * Return main settings page forms
     *
     * @param Request $request
     *
     * @throws Exception
     *
     * @return array
     */
    public function all_settings(Request $request)
    {
        $account_form             = $this->account($request);
        $personal_form            = $this->personal_info($request);
        $notifications_form_array = $this->notifications($request);

        return [
            '_template'           => 'settings/base.html.twig',
            'prof'                => $personal_form->createView(),
            'acc'                 => $account_form->createView(),
            'tabbed_forms_notify' => $notifications_form_array,
        ];
    }

    /**
     * Local user personal information panel
     */
    public function personal_info(Request $request)
    {
        $user            = Common::user();
        $actor           = $user->getActor();
        $extra           = ['self_tags' => $actor->getSelfTags()];
        $form_definition = [
            ['nickname',   TextType::class,      ['label' => _m('Nickname'),  'required' => true,  'help' => _m('1-64 lowercase letters or numbers, no punctuation or spaces.')]],
            ['full_name',  TextType::class,      ['label' => _m('Full Name'), 'required' => false, 'help' => _m('A full name is required, if empty it will be set to your nickname.')]],
            ['homepage',   TextType::class,      ['label' => _m('Homepage'),  'required' => false, 'help' => _m('URL of your homepage, blog, or profile on another site.')]],
            ['bio',        TextareaType::class,  ['label' => _m('Bio'),       'required' => false, 'help' => _m('Describe yourself and your interests.')]],
            ['location',   TextType::class,      ['label' => _m('Location'),  'required' => false, 'help' => _m('Where you are, like "City, State (or Region), Country".')]],
            ['self_tags',  TextType::class,      ['label' => _m('Self Tags'), 'required' => false, 'help' => _m('Tags for yourself (letters, numbers, -, ., and _), comma- or space-separated.'), 'transformer' => ArrayTransformer::class]],
            ['save_personal_info',       SubmitType::class,    ['label' => _m('Save personal info')]],
        ];
        $extra_step = function ($data, $extra_args) use ($user) {
            $user->setNickname($data['nickname']);
        };
        $form = Form::handle($form_definition, $request, $actor, $extra, $extra_step, [['self_tags' => $extra['self_tags']]]);

        return $form;
    }

    /**
     * Local user account information panel
     */
    public function account(Request $request)
    {
        $user = Common::user();
        // TODO Add support missing settings
        $form = Form::create([
            ['outgoing_email', TextType::class,        ['label' => _m('Outgoing email'), 'required' => true,  'help' => _m('Change the email we use to contact you')]],
            ['incoming_email', TextType::class,        ['label' => _m('Incoming email'), 'required' => true,  'help' => _m('Change the email you use to contact us (for posting, for instance)')]],
            ['old_password',   TextType::class,        ['label' => _m('Old password'),   'required' => false, 'help' => _m('Enter your old password for verification'), 'attr' => ['placeholder' => '********']]],
            FormFields::repeated_password(['required' => false]),
            ['language',       LocaleType::class,      ['label' => _m('Language'),       'required' => false, 'help' => _m('Your preferred language')]],
            ['phone_number',   PhoneNumberType::class, ['label' => _m('Phone number'),   'required' => false, 'help' => _m('Your phone number'),                        'data_class' => null]],
            ['save_account_info',           SubmitType::class,      ['label' => _m('Save account info')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (!is_null($data['old_password'])) {
                $data['password'] = $form->get('password')->getData();
                if (!($user->changePassword($data['old_password'], $data['password']))) {
                    throw new AuthenticationException(_m('The provided password is incorrect'));
                }
            }

            unset($data['old_password'], $data['password']);

            foreach ($data as $key => $val) {
                $method = 'set' . ucfirst(Formatting::snakeCaseToCamelCase($key));
                if (method_exists($user, $method)) {
                    $user->{$method}($val);
                }
            }
            DB::flush();
        }

        return $form;
    }

    /**
     * Local user notification settings tabbed panel
     */
    public function notifications(Request $request)
    {
        $user      = Common::user();
        $schema    = DB::getConnection()->getSchemaManager();
        $platform  = $schema->getDatabasePlatform();
        $columns   = Common::arrayRemoveKeys($schema->listTableColumns('user_notification_prefs'), ['user_id', 'transport', 'created', 'modified']);
        $form_defs = ['placeholder' => []];
        foreach ($columns as $name => $col) {
            $type     = $col->getType();
            $val      = $type->convertToPHPValue($col->getDefault(), $platform);
            $type_str = lcfirst(substr((string) $type, 1));
            $label    = str_replace('_', ' ', ucfirst($name));

            $labels = [
                'target_actor_id' => 'Target Actors',
                'dm'              => 'DM',
            ];

            $help = [
                'target_actor_id'       => 'If specified, these settings apply only to these profiles (comma- or space-separated list)',
                'activity_by_followed'  => 'Notify me when someone I follow has new activity',
                'mention'               => 'Notify me when mentions me in a notice',
                'reply'                 => 'Notify me when someone replies to a notice made by me',
                'follow'                => 'Notify me when someone follows me or asks for permission to do so',
                'favorite'              => 'Notify me when someone favorites one of my notices',
                'nudge'                 => 'Notify me when someone nudges me',
                'dm'                    => 'Notify me when someone sends me a direct message',
                'post_on_status_change' => 'Post a notice when my status in this service changes',
                'enable_posting'        => 'Enable posting from this service',
            ];

            switch ($type_str) {
            case Types::BOOLEAN:
                $form_defs['placeholder'][$name] = [$name, CheckboxType::class, ['data' => $val, 'label' => _m($labels[$name] ?? $label), 'help' => _m($help[$name])]];
                break;
            case Types::INTEGER:
                if ($name == 'target_actor_id') {
                    $form_defs['placeholder'][$name] = [$name, TextType::class, ['data' => $val, 'label' => _m($labels[$name]), 'help' => _m($help[$name])], 'transformer' => ActorArrayTransformer::class];
                }
                break;
            default:
                // @codeCoverageIgnoreStart
                throw new ServerException(_m('Internal server error'));
                Log::critical("Structure of table user_notification_prefs changed in a way not accounted to in notification settings ({$name}): " . $type_str);
                // @codeCoverageIgnoreEnd
            }
        }

        $form_defs['placeholder']['save'] = fn (string $transport, string $form_name) => [$form_name, SubmitType::class,
            ['label' => _m('Save notification settings for {transport}', ['transport' => $transport])], ];

        Event::handle('AddNotificationTransport', [&$form_defs]);
        unset($form_defs['placeholder']);

        $tabbed_forms = [];
        foreach ($form_defs as $transport_name => $f) {
            unset($f['save']);
            $form                          = Form::create($f);
            $tabbed_forms[$transport_name] = $form;

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                unset($data['translation_domain']);
                try {
                    [$ent, $is_update] = UserNotificationPrefs::createOrUpdate(
                        array_merge(['user_id' => $user->getId(), 'transport' => $transport_name], $data),
                        find_by_keys: ['user_id', 'transport']
                    );
                    if (!$is_update) {
                        DB::persist($ent);
                    }
                    DB::flush();
                    // @codeCoverageIgnoreStart
                } catch (\Exception $e) {
                    // Somehow, the exception doesn't bubble up in phpunit
                    dd($data, $e);
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        $tabbed_forms = F\map($tabbed_forms, function ($f) {
            return $f->createView();
        });
        return $tabbed_forms;
    }
}
