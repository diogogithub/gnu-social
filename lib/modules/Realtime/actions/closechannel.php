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
 * Action to close a channel
 *
 * @category  Realtime
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Action to close a channel
 *
 * @category  Realtime
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ClosechannelAction extends Action
{
    protected $channelKey = null;
    protected $channel = null;

    /**
     * For initializing members of the class.
     *
     * @param array $args misc. arguments
     *
     * @return boolean true
     * @throws ClientException
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);

        if (!$this->isPost()) {
            // TRANS: Client exception. Do not translate POST.
            throw new ClientException(_m('You have to POST it.'));
        }

        $this->channelKey = $this->trimmed('channelkey');

        if (empty($this->channelKey)) {
            // TRANS: Client exception thrown when the channel key argument is missing.
            throw new ClientException(_m('No channel key argument.'));
        }

        $this->channel = Realtime_channel::getKV('channel_key', $this->channelKey);

        if (empty($this->channel)) {
            // TRANS: Client exception thrown when referring to a non-existing channel.
            throw new ClientException(_m('No such channel.'));
        }

        return true;
    }

    /**
     * Handler method
     *
     * @return void
     */
    public function handle(): void
    {
        $this->channel->decrement();

        header('HTTP/1.1 204 No Content');

        return;
    }

    /**
     * Return true if read only.
     *
     * MAY override
     *
     * @param array $args other arguments
     *
     * @return bool is read only action?
     */
    public function isReadOnly($args): bool
    {
        return false;
    }
}
