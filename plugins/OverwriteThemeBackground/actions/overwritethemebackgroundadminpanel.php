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
 * UI to overwrite his GNU social instance's background
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Apply/Store the custom background preferences
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OverwriteThemeBackgroundAdminPanelAction extends AdminPanelAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */
    public function title(): string
    {
        return _m('Overwrite Theme Background');
    }

    /**
     * Instructions for use
     *
     * @return string instructions for use
     */
    public function getInstructions(): string
    {
        return _m('Customize your theme\'s background easily');
    }

    /**
     * Show the site admin panel form
     *
     * @return void
     */
    public function showForm()
    {
        $form = new OverwriteThemeBackgroundAdminPanelForm($this);
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
        static $settings = [
            'overwritethemebackground' => [
                'background-color',
                'background-image',
                'sslbackground-image',
                'background-repeat',
                'background-attachment',
                'background-position'
            ]
        ];

        $values = [];

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
     * Validate form values
     *
     * @param $values
     * @throws ClientException
     */
    public function validate(&$values)
    {
        // Validate background
        if (!empty($values['overwritethemebackground']['background-image']) &&
            !common_valid_http_url($values['overwritethemebackground']['background-image'])) {
            // TRANS: Client error displayed when a background URL is not valid.
            $this->clientError(_m('Invalid background URL.'));
        }

        if (!empty($values['overwritethemebackground']['sslbackground-image']) &&
            !common_valid_http_url($values['overwritethemebackground']['sslbackground-image'], true)) {
            // TRANS: Client error displayed when a SSL background URL is invalid.
            $this->clientError(_m('Invalid SSL background URL.'));
        }
    }
}

/**
 * Friendly UI for setting the custom background preferences
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OverwriteThemeBackgroundAdminPanelForm extends AdminForm
{
    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    public function id()
    {
        return 'form_site_admin_panel';
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
        return common_local_url('overwritethemebackgroundAdminPanel');
    }

    /**
     * Data elements of the form
     *
     * @return void
     */
    public function formData()
    {
        $this->out->elementStart('fieldset', ['id' => 'settings_site_background']);
        // TRANS: Fieldset legend for form to change background.
        $this->out->element('legend', null, _m('Background'));
        $this->out->elementStart('ul', 'form_data');

        /* Background colour */

        $this->li();
        $this->input(
            'background-color',
            // TRANS: Field label for GNU social site background.
            _m('Site background color'),
            // TRANS: Title for field label for GNU social site background.
            'Background color for the site (hexadecimal with #).',
            'overwritethemebackground'
        );
        $this->unli();

        /* Background image */

        $this->li();
        $this->input(
            'background-image',
            // TRANS: Field label for GNU social site background.
            _m('Site background'),
            // TRANS: Title for field label for GNU social site background.
            'Background for the site (full URL).',
            'overwritethemebackground'
        );
        $this->unli();

        $this->li();
        $this->input(
            'sslbackground-image',
            // TRANS: Field label for SSL GNU social site background.
            _m('SSL background'),
            // TRANS: Title for field label for SSL GNU social site background.
            'Background to show on SSL pages (full URL).',
            'overwritethemebackground'
        );
        $this->unli();

        /* Background repeat */

        $this->li();
        // TRANS: Dropdown label on site settings panel.
        $this->out->dropdown(
            'background-repeat',
            _m('Background repeat'),
            // TRANS: Dropdown title on site settings panel.
            ['Repeat horizontally and vertically', 'Repeat Horizontally', 'Repeat Vertically', 'Don\'t repeat'],
            _m('repeat horizontally and/or vertically'),
            false,
            common_config('overwritethemebackground', 'background-repeat') ?? 'repeat'
        );
        $this->unli();

        /* Background attachment */

        $this->li();
        // TRANS: Dropdown label on site settings panel.
        $this->out->dropdown(
            'background-attachment',
            _m('Background attachment'),
            // TRANS: Dropdown title on site settings panel.
            ['Scroll with page', 'Stay fixed'],
            _m('Whether the background image should scroll or be fixed (will not scroll with the rest of the page)'),
            false,
            common_config('overwritethemebackground', 'background-attachment') ?? 'scroll'
        );
        $this->unli();

        /* Background position */

        $background_position_options = [
            'initial',
            'left top',
            'left center',
            'left bottom',
            'right top',
            'right center',
            'right bottom',
            'center top',
            'center center',
            'center bottom'
        ];
        $this->li();
        // TRANS: Dropdown label on site settings panel.
        $this->out->dropdown(
            'background-position',
            _m('Background position'),
            // TRANS: Dropdown title on site settings panel.
            $background_position_options,
            _m('Sets the starting position of a background image'),
            false,
            common_config('overwritethemebackground', 'background-attachment') ?? 'initial'
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
            // TRANS: Button text for saving site settings.
            _m('BUTTON', 'Save'),
            'submit',
            null,
            // TRANS: Button title for saving site settings.
            _m('Save the site settings.')
        );
    }
}
