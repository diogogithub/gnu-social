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
 * Action for showing a single message
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ShowmessageAction extends Action
{
    protected $message    = null;
    protected $from       = null;
    protected $attentions = null;
    protected $user       = null;

    /**
     * Load attributes based on database arguments.
     *
     * @param array $args $_REQUEST array
     * @return bool success flag
     */
    function prepare($args = [])
    {
        parent::prepare($args);

        if (!$this->trimmed('message')) {
            return true;
        }

        $this->message = Notice::getKV('id', $this->trimmed('message'));

        if (!$this->message instanceof Notice) {
            // TRANS: Client error displayed requesting a single message that does not exist.
            $this->clientError(_m('No such message.'), 404);
        }

        $this->from       = $this->message->getProfile();
        $this->attentions = $this->message->getAttentionProfiles();

        $this->user = common_current_user();

        if (empty($this->user) || $this->user->getID() != $this->from->getID()) {

            $receiver = false;
            foreach ($this->attentions as $attention) {
                if ($this->user->getID() == $attention->getID()) {
                    $receiver = true;
                    break;
                }
            }
            
            if (!$receiver) {
                // TRANS: Client error displayed requesting a single direct message the requesting user was not a party in.
                throw new ClientException(_m('Only the sender and recipients may read this message.'), 403);
            }
        }

        return true;
    }

    /**
     * Handler method.
     * 
     * @return void
     */
    function handle()
    {
        $this->showPage();
    }

    /**
     * Title of the page.
     *
     * @return string page title
     */
    function title() : string
    {
        if ($this->user->getID() == $this->from->getID()) {
            if (sizeof($this->attentions) > 1) {
                return sprintf(_m('Message to many on %1$s'), common_exact_date($this->message->getCreated()));
            } else {
                $to = Profile::getKV('id', $this->attentions[0]->getID());
                // @todo FIXME: Might be nice if the timestamp could be localised.
                // TRANS: Page title for single direct message display when viewing user is the sender.
                // TRANS: %1$s is the addressed user's nickname, $2$s is a timestamp.
                return sprintf(_m('Message to %1$s on %2$s'),
                               $to->getBestName(),
                               common_exact_date($this->message->getCreated()));
            }
        } else {
            // @todo FIXME: Might be nice if the timestamp could be localised.
            // TRANS: Page title for single message display.
            // TRANS: %1$s is the sending user's nickname, $2$s is a timestamp.
            return sprintf(_m('Message from %1$s on %2$s'),
                            $this->from->getBestName(),
                            common_exact_date($this->message->getCreated()));
        }
    }

    /**
     * Show content.
     *
     * @return void
     */
    function showContent()
    {
        $this->elementStart('ul', 'notices messages');
        $ml = new ShowMessageListItem($this, $this->message, $this->user, $this->from, $this->attentions);
        $ml->show();
        $this->elementEnd('ul');
    }

    /**
     * Is this action read-only?
     *
     * @param array $args other arguments
     * @return bool true if read-only action, false otherwise
     */
    function isReadOnly($args) : bool
    {
        return true;
    }

    /**
     * Don't show aside
     *
     * @return void
     */
    function showAside() {

    }
}

/**
 * showmessage action's MessageListItem widget.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ShowMessageListItem extends MessageListItem
{
    protected $user;
    protected $from;
    protected $attentions;

    function __construct($out, $message, $user, $from, $attentions)
    {
        parent::__construct($out, $message);

        $this->user       = $user;
        $this->from       = $from;
        $this->attentions = $attentions;
    }

    function getMessageProfile() : ?Profile
    {
        return $this->user->getID() == $this->from->getID() ?
               $this->attentions[0] : $this->from; 
    }
}
