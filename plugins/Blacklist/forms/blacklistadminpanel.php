<?php

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * Admin panel form for blacklist panel
 *
 * @category Admin
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPLv3
 * @link     http://status.net/
 */
class BlacklistAdminPanelForm extends Form
{
    /**
     * ID of the form
     *
     * @return string ID
     */
    function id()
    {
        return 'blacklistadminpanel';
    }

    /**
     * Class of the form
     *
     * @return string class
     */
    function formClass()
    {
        return 'form_settings';
    }

    /**
     * Action we post to
     *
     * @return string action URL
     */
    function action()
    {
        return common_local_url('blacklistadminpanel');
    }

    /**
     * Show the form controls
     *
     * @return void
     */
    function formData()
    {
        $this->out->elementStart('ul', 'form_data');

        $this->out->elementStart('li');

        $nickPatterns = Nickname_blacklist::getPatterns();

        // TRANS: Field label in blacklist plugin administration panel.
        $this->out->textarea('blacklist-nicknames', _m('Nicknames'),
                             implode("\r\n", $nickPatterns),
                             // TRANS: Field title in blacklist plugin administration panel.
                             _m('Patterns of nicknames to block, one per line.'));
        $this->out->elementEnd('li');

        $urlPatterns = Homepage_blacklist::getPatterns();

        $this->out->elementStart('li');
        // TRANS: Field label in blacklist plugin administration panel.
        $this->out->textarea('blacklist-urls', _m('URLs'),
                             implode("\r\n", $urlPatterns),
                             // TRANS: Field title in blacklist plugin administration panel.
                             _m('Patterns of URLs to block, one per line.'));
        $this->out->elementEnd('li');

        $this->out->elementEnd('ul');
    }

    /**
     * Buttons for submitting
     *
     * @return void
     */
    function formActions()
    {
        $this->out->submit('submit',
                           // TRANS: Button text in blacklist plugin administration panel to save settings.
                           _m('BUTTON','Save'),
                           'submit',
                           null,
                           // TRANS: Button title in blacklist plugin administration panel to save settings.
                           _m('Save site settings.'));
    }
}
