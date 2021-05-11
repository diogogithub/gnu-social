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
 * Sitemap administration panel
 *
 * @category  Sitemap
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Administer sitemap settings
 *
 * @category  Sitemap
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SitemapadminpanelAction extends AdminPanelAction
{
    /**
     * Returns the page title
     *
     * @return string page title
     */
    public function title()
    {
        // TRANS: Title for sitemap.
        return _m('Sitemap');
    }

    /**
     * Instructions for using this form.
     *
     * @return string instructions
     */
    public function getInstructions()
    {
        // TRANS: Instructions for sitemap.
        return _m('Sitemap settings for this StatusNet site');
    }

    /**
     * Show the site admin panel form
     *
     * @return void
     */
    public function showForm()
    {
        $form = new SitemapAdminPanelForm($this);
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
        static $settings = array('sitemap' => array('yahookey', 'bingkey'));

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

    public function validate(&$values)
    {
    }
}

/**
 * Form for the sitemap admin panel
 */
class SitemapAdminPanelForm extends AdminForm
{
    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    public function id()
    {
        return 'form_sitemap_admin_panel';
    }

    /**
     * class of the form
     *
     * @return string class of the form
     */
    public function formClass()
    {
        return 'form_sitemap';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */
    public function action()
    {
        return common_local_url('sitemapadminpanel');
    }

    /**
     * Data elements of the form
     *
     * @return void
     */
    public function formData()
    {
        $this->out->elementStart('ul', 'form_data');
        $this->li();
        $this->input(
            'yahookey',
            // TRANS: Field label.
            _m('Yahoo key'),
            // TRANS: Title for field label.
            _m('Yahoo! Site Explorer verification key.'),
            'sitemap'
        );
        $this->unli();
        $this->li();
        $this->input(
            'bingkey',
            // TRANS: Field label.
            _m('Bing key'),
            // TRANS: Title for field label.
            _m('Bing Webmaster Tools verification key.'),
            'sitemap'
        );
        $this->unli();
        $this->out->elementEnd('ul');
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
            // TRANS: Submit button text to save sitemap settings.
            _m('BUTTON', 'Save'),
            'submit',
            null,
            // TRANS: Submit button title to save sitemap settings.
            _m('Save sitemap settings.')
        );
    }
}
