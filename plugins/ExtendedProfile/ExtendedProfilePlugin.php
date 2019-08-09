<?php
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

/**
 * Extra profile bio-like fields and allows administrators to define
 * additional profile fields for the users of a GNU social installation.
 *
 * @category  Widget
 * @package   GNU social
 * @author    Brion Vibber <brion@status.net>
 * @author    Max Shinn <trombonechamp@gmail.com>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

include_once __DIR__ . '/lib/profiletools.php';

class ExtendedProfilePlugin extends Plugin
{
    const PLUGIN_VERSION = '3.0.0';

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'ExtendedProfile',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Brion Vibber, Samantha Doherty, Zach Copley, Max Shinn, Diogo Cordeiro',
            'homepage' => 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/ExtendedProfile',
            // TRANS: Module description.
            'rawdescription' => _m('UI extensions for additional profile fields.')
        ];

        return true;
    }

    /**
     * Add paths to the router table
     *
     * Hook for RouterInitialized event.
     *
     * @param URLMapper $m URL mapper
     *
     * @return bool hook return
     * @throws Exception
     */
    public function onStartInitializeRouter(URLMapper $m)
    {
        $m->connect(
            ':nickname/detail',
            ['action' => 'profiledetail'],
            ['nickname' => Nickname::DISPLAY_FMT]
        );
        $m->connect(
            '/settings/profile/finduser',
            ['action' => 'Userautocomplete']
        );
        $m->connect(
            'settings/profile/detail',
            ['action' => 'profiledetailsettings']
        );
        $m->connect(
            'admin/profilefields',
            ['action' => 'profilefieldsAdminPanel']
        );

        return true;
    }

    public function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable('profile_detail', Profile_detail::schemaDef());
        $schema->ensureTable('gnusocialprofileextensionfield', GNUsocialProfileExtensionField::schemaDef());
        $schema->ensureTable('gnusocialprofileextensionresponse', GNUsocialProfileExtensionResponse::schemaDef());
        return true;
    }

    public function onEndShowAccountProfileBlock(HTMLOutputter $out, Profile $profile)
    {
        $user = User::getKV('id', $profile->id);
        if ($user) {
            $url = common_local_url('profiledetail', ['nickname' => $user->nickname]);
            // TRANS: Link text on user profile page leading to extended profile page.
            $out->element('a', ['href' => $url, 'class' => 'profiledetail'], _m('More details...'));
        }
    }

    /**
     * Menu item for personal subscriptions/groups area
     *
     * @param Action $action action being executed
     *
     * @return bool hook return
     * @throws Exception
     */
    public function onEndAccountSettingsNav(Action $action)
    {
        $action_name = $action->trimmed('action');

        $action->menuItem(
            common_local_url('profiledetailsettings'),
            // TRANS: Extended profile plugin menu item on user settings page.
            _m('MENU', 'Full Profile'),
            // TRANS: Extended profile plugin tooltip for user settings menu item.
            _m('Change your extended profile settings'),
            $action_name === 'profiledetailsettings'
        );

        return true;
    }

    /*public function onEndProfileFormData(Action $action): bool
    {
        $fields = GNUsocialProfileExtensionField::allFields();
        $user = common_current_user();
        $profile = $user->getProfile();
        gnusocial_profile_merge($profile);
        foreach ($fields as $field) {
            $action->elementStart('li');
            $fieldname = $field->systemname;
            if ($field->type == 'str') {
                $action->input(
                    $fieldname,
                    $field->title,
                    ($action->arg($fieldname)) ? $action->arg($fieldname) : $profile->$fieldname,
                    $field->description
                );
            } elseif ($field->type == 'text') {
                $action->textarea(
                    $fieldname,
                    $field->title,
                    ($action->arg($fieldname)) ? $action->arg($fieldname) : $profile->$fieldname,
                    $field->description
                );
            }
            $action->elementEnd('li');
        }
        return true;
    }

    public function onEndProfileSaveForm(Action $action): bool
    {
        $fields = GNUsocialProfileExtensionField::allFields();
        $user = common_current_user();
        $profile = $user->getProfile();
        foreach ($fields as $field) {
            $val = $action->trimmed($field->systemname);

            $response = new GNUsocialProfileExtensionResponse();
            $response->profile_id = $profile->id;
            $response->extension_id = $field->id;

            if ($response->find()) {
                $response->fetch();
                $response->value = $val;
                if ($response->validate()) {
                    if (empty($val)) {
                        $response->delete();
                    } else {
                        $response->update();
                    }
                }
            } else {
                $response->value = $val;
                $response->insert();
            }
        }
        return true;
    }*/

    public function onEndShowStyles(Action $action): bool
    {
        $action->cssLink('/plugins/ExtendedProfile/css/profiledetail.css');
        return true;
    }

    public function onEndShowScripts(Action $action): bool
    {
        $action->script('plugins/ExtendedProfile/js/profiledetail.js');
        return true;
    }

    public function onEndAdminPanelNav(AdminPanelNav $nav): bool
    {
        if (AdminPanelAction::canAdmin('profilefields')) {
            $action_name = $nav->action->trimmed('action');

            $nav->out->menuItem(
                '/admin/profilefields',
                _m('Profile Fields'),
                _m('Custom profile fields'),
                $action_name == 'profilefieldsadminpanel',
                'nav_profilefields_admin_panel'
            );
        }

        return true;
    }
}
