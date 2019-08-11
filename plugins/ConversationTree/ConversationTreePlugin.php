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
 * The ConversationTree plugin displays conversation replies in a hierarchical
 * manner like StatusNet pre-v1.0 used to.
 *
 * @category  UI
 * @package   ConversationTreePlugin
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class ConversationTreePlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    public function onStartShowConversation(Action $action, Conversation $conv, Profile $scoped = null): bool
    {
        $nl = new ConversationTree($conv->getNotices($action->getScoped()), $action);
        $nl->show();
        return false;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'ConversationTree',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Evan Prodromou, Mikael Nordfeldth',
            'homepage' => 'http://gnu.io/',
            'rawdescription' =>
            // TRANS: Module description.
                _m('Enables conversation tree view.')
        ];

        return true;
    }
}
