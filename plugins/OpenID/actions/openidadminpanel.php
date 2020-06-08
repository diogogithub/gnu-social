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
 * OpenID bridge administration panel
 *
 * @category  Settings
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Administer global OpenID settings
 *
 * @category  Admin
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OpenidadminpanelAction extends AdminPanelAction
{
    /**
     * Returns the page title
     *
     * @return string page title
     */
    public function title()
    {
        // TRANS: Title for OpenID bridge administration page.
        return _m('TITLE', 'OpenID Settings');
    }

    /**
     * Instructions for using this form.
     *
     * @return string instructions
     */
    public function getInstructions()
    {
        // TRANS: Page instructions.
        return _m('OpenID settings');
    }

    /**
     * Show the OpenID admin panel form
     *
     * @return void
     */
    public function showForm()
    {
        $form = new OpenIDAdminPanelForm($this);
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
            'openid' => array('trusted_provider', 'required_team')
        );

        static $booleans = array(
            'openid' => array('append_username'),
            'site' => array('openidonly')
        );

        $values = array();

        foreach ($settings as $section => $parts) {
            foreach ($parts as $setting) {
                $values[$section][$setting]
                    = $this->trimmed($setting);
            }
        }

        foreach ($booleans as $section => $parts) {
            foreach ($parts as $setting) {
                $values[$section][$setting]
                    = ($this->boolean($setting)) ? 1 : 0;
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

        foreach ($booleans as $section => $parts) {
            foreach ($parts as $setting) {
                Config::save($section, $setting, $values[$section][$setting]);
            }
        }

        $config->query('COMMIT');

        return;
    }

    public function validate(&$values)
    {
        // Validate consumer key and secret (can't be too long)

        if (mb_strlen($values['openid']['trusted_provider']) > 255) {
            $this->clientError(
                // TRANS: Client error displayed when OpenID provider URL is too long.
                _m('Invalid provider URL. Maximum length is 255 characters.')
            );
        }

        if (mb_strlen($values['openid']['required_team']) > 255) {
            $this->clientError(
                // TRANS: Client error displayed when Launchpad team name is too long.
                _m('Invalid team name. Maximum length is 255 characters.')
            );
        }
    }
}

class OpenIDAdminPanelForm extends AdminForm
{
    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    public function id()
    {
        return 'openidadminpanel';
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
        return common_local_url('openidadminpanel');
    }

    /**
     * Data elements of the form
     *
     * @return void
     *
     * @todo Some of the options could prevent users from logging in again.
     *       Make sure that the acting administrator has a valid OpenID matching,
     *       or more carefully warn folks.
     */
    public function formData()
    {
        $this->out->elementStart(
            'fieldset',
            array('id' => 'settings_openid')
        );
        // TRANS: Fieldset legend.
        $this->out->element('legend', null, _m('LEGEND', 'Trusted provider'));
        $this->out->element(
            'p',
            'form_guide',
            // TRANS: Form guide.
            _m('By default, users are allowed to authenticate with any OpenID provider. ' .
               'If you are using your own OpenID service for shared sign-in, ' .
               'you can restrict access to only your own users here.')
        );
        $this->out->elementStart('ul', 'form_data');

        $this->li();
        $this->input(
            'trusted_provider',
            // TRANS: Field label.
            _m('Provider URL'),
            // TRANS: Field title.
            _m('All OpenID logins will be sent to this URL; other providers may not be used.'),
            'openid'
        );
        $this->unli();

        $this->li();
        $this->out->checkbox(
            // TRANS: Checkbox label.
            'append_username',
            _m('Append a username to base URL'),
            (bool) $this->value('append_username', 'openid'),
            // TRANS: Checkbox title.
            _m('Login form will show the base URL and prompt for a username to add at the end. Use when OpenID provider URL should be the profile page for individual users.'),
            'true'
        );
        $this->unli();

        $this->li();
        $this->input(
            'required_team',
            // TRANS: Field label.
            _m('Required team'),
            // TRANS: Field title.
            _m('Only allow logins from users in the given team (Launchpad extension).'),
            'openid'
        );
        $this->unli();

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');

        $this->out->elementStart(
            'fieldset',
            array('id' => 'settings_openid-options')
        );
        // TRANS: Fieldset legend.
        $this->out->element('legend', null, _m('LEGEND', 'Options'));

        $this->out->elementStart('ul', 'form_data');

        $this->li();

        $this->out->checkbox(
            // TRANS: Checkbox label.
            'openidonly',
            _m('Enable OpenID-only mode'),
            (bool) $this->value('openidonly', 'site'),
            // TRANS: Checkbox title.
            _m('Require all users to login via OpenID. Warning: disables password authentication for all users!'),
            'true'
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
        // TRANS: Button text to save OpenID settings.
        $this->out->submit(
            'submit',
            _m('BUTTON', 'Save'),
            'submit',
            null,
            // TRANS: Button title to save OpenID settings.
            _m('Save OpenID settings.')
        );
    }
}
