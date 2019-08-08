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
 * Allows administrators to define additional profile fields for the users of a GNU social installation.
 *
 * @category  Widget
 * @package   GNU social
 * @author    Max Shinn <trombonechamp@gmail.com>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

include_once __DIR__ . '/lib/profiletools.php';

class GNUsocialProfileExtensionsPlugin extends Plugin
{
    public function onCheckSchema(): bool
    {
        $schema = Schema::get();
        $schema->ensureTable('gnusocialprofileextensionfield', GNUsocialProfileExtensionField::schemaDef());
        $schema->ensureTable('gnusocialprofileextensionresponse', GNUsocialProfileExtensionResponse::schemaDef());
        return true;
    }

    public function onRouterInitialized($m): bool
    {
        $m->connect('admin/profilefields', ['action' => 'profilefieldsAdminPanel']);
        return true;
    }

    public function onEndProfileFormData($action): bool
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

    public function onEndProfileSaveForm($action): bool
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
    }

    public function onEndShowStyles($action): bool
    {
        $action->cssLink('/plugins/GNUsocialProfileExtensions/res/style.css');
        return true;
    }

    public function onEndShowScripts($action): bool
    {
        $action->script('plugins/GNUsocialProfileExtensions/js/profile.js');
        return true;
    }

    public function onEndAdminPanelNav($nav): bool
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
