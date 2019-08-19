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
 * GNUsocial implementation of Direct Messages
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Form for posting a direct message
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class MessageForm extends Form
{
    protected $to      = null;
    protected $content = null;

    /**
     * Constructor.
     *
     * @param HTMLOutputter $out output channel
     * @param array|null $formOpts
     */
    function __construct(HTMLOutputter $out = null, ?array $formOpts = null)
    {
        parent::__construct($out);

        if (isset($formOpts['to'])) {
            $this->to = $formOpts['to'];
        }

        $this->content = $formOpts['content'] ?? '';
    }

    /**
     * ID of the form.
     *
     * @return string ID of the form
     */
    function id(): string
    {
        return 'form_notice-direct';
    }

    /**
     * Class of the form.
     *
     * @return string class of the form
     */
    function formClass(): string
    {
        return 'form_notice ajax-notice';
    }

    /**
     * Action of the form.
     *
     * @return string URL of the action
     */
    function action(): string
    {
        return common_local_url('newmessage');
    }

    /**
     * Legend of the Form.
     *
     * @return void
     */
    function formLegend()
    {
        // TRANS: Form legend for direct notice.
        $this->out->element('legend', null, _m('Send a direct notice'));
    }

    /**
     * Data elements.
     *
     * @return void
     */
    function formData()
    {
        $user = common_current_user();

        $recipients = [];
        $default    = 'default';

        $subs   = $user->getSubscribed();
        $n_subs = 0;

        // Add local-subscriptions
        while ($subs->fetch()) {
            $n_subs++;
            if ($subs->isLocal()) {
                $value = 'profile:'.$subs->getID();
                try {
                    $recipients[$value] = substr($subs->getAcctUri(), 5) . " [{$subs->getBestName()}]";
                } catch (ProfileNoAcctUriException $e) {
                    $recipients[$value] = "[?@?] " . $e->profile->getBestName();
                }
            }
        }

        if (sizeof($recipients) < $n_subs) {
            // some subscriptions aren't local and therefore weren't added,
            // worth checking if others want to add them
            Event::handle('FillDirectMessageRecipients', [$user, &$recipients]);
        }

        // if we came from a profile page, then lets make the message receiver visible
        if (!is_null($this->to)) {
            if (isset($recipients['profile:'.$this->to->getID()])) {
                $default = 'profile' . $this->to->getID(); 
            } else {
                try {
                    if ($this->to->isLocal()) {
                        $this->content = "@{$this->to->getNickname()} {$this->content}";
                    } else {
                        $this->content = substr($this->to->getAcctUri(), 5) . " {$this->content}";
                    }
                } catch (ProfileNoAcctUriException $e) {
                    // well, I'm no magician
                }
            }
        }

        if ($default === 'default') {
            // TRANS: Label entry in drop-down selection box in direct-message inbox/outbox.
            // TRANS: This is the default entry in the drop-down box, doubling as instructions
            // TRANS: and a brake against accidental submissions with the first user in the list.
            $recipients[$default] = empty($recipients) ? _m('No subscriptions') : _m('Select recipient:');
        }

        asort($recipients);

        // TRANS: Dropdown label in direct notice form.
        $this->out->dropdown('to-box',
                             _m('To'),
                             $recipients,
                             null,
                             false,
                             $default);

        $this->out->element('textarea',
                            ['class' => 'notice_data-text',
                             'cols'  => 35,
                             'rows'  => 4,
                             'name'  => 'content'],
                             $this->content);

        $contentLimit = MessageModel::maxContent();

        if ($contentLimit > 0) {
            $this->out->element('span',
                                ['class' => 'count'],
                                $contentLimit);
        }
    }

    /**
     * Action elements.
     *
     * @return void
     */
    function formActions()
    {
        $this->out->element('input',
                            ['id'    => 'notice_action-submit',
                             'class' => 'submit',
                             'name'  => 'message_send',
                             'type'  => 'submit',
                             // TRANS: Button text for sending a direct notice.
                             'value' => _m('Send button for direct notice', 'Send')]);
    }

    /**
     * Show the form.
     *
     * @return void
     */
    function show()
    {
        $this->elementStart('div', 'input_forms');
        $this->elementStart('div',
                            ['id'    => 'input_form_direct',
                             'class' => 'input_form current nonav']);

        parent::show();

        $this->elementEnd('div');
        $this->elementEnd('div');
    }
}
