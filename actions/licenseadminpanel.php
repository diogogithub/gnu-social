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
 * License administration panel
 *
 * @category  Settings
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * License settings
 *
 * @category  Admin
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class LicenseadminpanelAction extends AdminPanelAction
{
    /**
     * Returns the page title
     *
     * @return string page title
     */

    public function title()
    {
        // TRANS: User admin panel title
        return _m('TITLE', 'License');
    }

    /**
     * Instructions for using this form.
     *
     * @return string instructions
     */
    public function getInstructions()
    {
        // TRANS: Form instructions for the site license admin panel.
        return _('License for this StatusNet site');
    }

    /**
     * Show the site admin panel form
     *
     * @return void
     */
    public function showForm()
    {
        $form = new LicenseAdminPanelForm($this);
        $form->show();
        return;
    }

    /**
     * Save settings from the form
     *
     * @return void
     */
    public function saveSettings()
    {
        static $settings = array(
            'license' => array('type', 'owner', 'url', 'title', 'image')
        );

        $values = array();

        foreach ($settings as $section => $parts) {
            foreach ($parts as $setting) {
                $values[$section][$setting] = $this->trimmed($setting);
            }
        }

        // This throws an exception on validation errors

        $this->validate($values);

        // assert(all values are valid);

        $config = new Config();

        $config->query('START TRANSACTION');

        foreach ($settings as $section => $parts) {
            foreach ($parts as $setting) {
                Config::save($section, $setting, $values[$section][$setting]);
            }
        }

        $config->query('COMMIT');

        return;
    }

    /**
     * Validate License admin form values
     *
     * @param array &$values from the form
     *
     * @return nothing
     */
    public function validate(&$values)
    {
        // Validate license type (shouldn't have to do it, but just in case)

        $types = array('private', 'allrightsreserved', 'cc');

        if (!in_array($values['license']['type'], $types)) {
            // TRANS: Client error displayed selecting an invalid license in the license admin panel.
            $this->clientError(_('Invalid license selection.'));
        }

        // Make sure the user has set an owner if the site has a private
        // license

        if ($values['license']['type'] == 'allrightsreserved'
            && empty($values['license']['owner'])
        ) {
            $this->clientError(
                // TRANS: Client error displayed when not specifying an owner for the all rights reserved license in the license admin panel.
                _('You must specify the owner of the content when using the All Rights Reserved license.')
            );
        }

        // Make sure the license title is not too long
        if (mb_strlen($values['license']['type']) > 255) {
            $this->clientError(
                // TRANS: Client error displayed selecting a too long license title in the license admin panel.
                _('Invalid license title. Maximum length is 255 characters.')
            );
        }

        // URLs should be set for cc license

        if ($values['license']['type'] == 'cc') {
            if (!common_valid_http_url($values['license']['url'])) {
                // TRANS: Client error displayed specifying an invalid license URL in the license admin panel.
                $this->clientError(_('Invalid license URL.'));
            }
            if (!common_valid_http_url($values['license']['image'])) {
                // TRANS: Client error displayed specifying an invalid license image URL in the license admin panel.
                $this->clientError(_('Invalid license image URL.'));
            }
        }

        // can be either blank or a valid URL for private & allrightsreserved

        if (!empty($values['license']['url'])) {
            if (!common_valid_http_url($values['license']['url'])) {
                // TRANS: Client error displayed specifying an invalid license URL in the license admin panel.
                $this->clientError(_('License URL must be blank or a valid URL.'));
            }
        }

        // can be either blank or a valid URL for private & allrightsreserved

        if (!empty($values['license']['image'])) {
            if (!common_valid_http_url($values['license']['image'])) {
                // TRANS: Client error displayed specifying an invalid license image URL in the license admin panel.
                $this->clientError(_('License image must be blank or valid URL.'));
            }
        }
    }
}

class LicenseAdminPanelForm extends AdminForm
{
    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    public function id()
    {
        return 'licenseadminpanel';
    }

    /**
     * class of the form
     *
     * @return string class of the form
     */
    public function formClass()
    {
        return 'form_settings';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */

    public function action()
    {
        return common_local_url('licenseadminpanel');
    }

    /**
     * Data elements of the form
     *
     * @return void
     */

    public function formData()
    {
        $this->out->elementStart(
            'fieldset',
            ['id' => 'settings_license-selection']
        );
        // TRANS: Form legend in the license admin panel.
        $this->out->element('legend', null, _('License selection'));
        $this->out->elementStart('ul', 'form_data');

        $this->li();

        $types = array(
            // TRANS: License option in the license admin panel.
            'private' => _('Private'),
            // TRANS: License option in the license admin panel.
            'allrightsreserved' => _('All Rights Reserved'),
            // TRANS: License option in the license admin panel.
            'cc' => _('Creative Commons')
        );

        $this->out->dropdown(
            'type',
            // TRANS: Dropdown field label in the license admin panel.
            _('Type'),
            $types,
            // TRANS: Dropdown field instructions in the license admin panel.
            _('Select a license.'),
            false,
            $this->value('type', 'license')
        );

        $this->unli();

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');

        $this->out->elementStart(
            'fieldset',
            array('id' => 'settings_license-details')
        );
        // TRANS: Form legend in the license admin panel.
        $this->out->element('legend', null, _('License details'));
        $this->out->elementStart('ul', 'form_data');

        $this->li();
        $this->input(
            'owner',
            // TRANS: Field label in the license admin panel.
            _('Owner'),
            // TRANS: Field title in the license admin panel.
            _('Name of the owner of the site\'s content (if applicable).'),
            'license'
        );
        $this->unli();

        $this->li();
        $this->input(
            'title',
            // TRANS: Field label in the license admin panel.
            _('License Title'),
            // TRANS: Field title in the license admin panel.
            _('The title of the license.'),
            'license'
        );
        $this->unli();

        $this->li();
        $this->input(
            'url',
            // TRANS: Field label in the license admin panel.
            _('License URL'),
            // TRANS: Field title in the license admin panel.
            _('URL for more information about the license.'),
            'license'
        );
        $this->unli();

        $this->li();
        $this->input(
            // TRANS: Field label in the license admin panel.
            'image',
            _('License Image URL'),
            // TRANS: Field title in the license admin panel.
            _('URL for an image to display with the license.'),
            'license'
        );
        $this->unli();

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');
    }

    /**
     * Action elements
     *
     * @return void
     */
    public function formActions()
    {
        $this->out->submit(
            'submit',
            // TRANS: Button text in the license admin panel.
            _m('BUTTON', 'Save'),
            'submit',
            null,
            // TRANS: Button title in the license admin panel.
            _('Save license settings.')
        );
    }
}
