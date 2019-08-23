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

// require needed abstractions first
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'messagelist.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'messagelistitem.php';

// Import plugin libs
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
    require_once $filename;
}
// Import plugin models
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
    require_once $filename;
}

/**
 * @category  Plugin
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DirectMessagePlugin extends Plugin
{
    const PLUGIN_VERSION = '3.0.0';

    public function onRouterInitialized(URLMapper $m)
    {
        // web front-end actions
        $m->connect('message/new',
                    ['action' => 'newmessage']);
        $m->connect('message/new?to=:to',
                    ['action' => 'newmessage'],
                    ['to'     => '[0-9]+']);

        $m->connect('message/:message',
                    ['action'  => 'showmessage'],
                    ['message' => '[0-9]+']);

        // direct messages
        $m->connect('api/direct_messages.:format',
                    ['action' => 'ApiDirectMessage'],
                    ['format' => '(xml|json|rss|atom)']);
        $m->connect('api/direct_messages/sent.:format',
                    ['action' => 'ApiDirectMessage',
                     'sent'   => true],
                    ['format' => '(xml|json|rss|atom)']);
        $m->connect('api/direct_messages/new.:format',
                    ['action' => 'ApiDirectMessageNew'],
                    ['format' => '(xml|json)']);

        return true;
    }

    /**
     * Are we allowed to perform a certain command over the API?
     * 
     * @param Command $cmd
     * @param bool &$supported
     * @return bool hook value
     */
    public function onCommandSupportedAPI(Command $cmd, ?bool &$supported) : bool
    {
        $supported = $supported || $cmd instanceof MessageCommand;
        return true;
    }

    /**
     * EndInterpretCommand will handle the 'd' and 'dm' commands.
     *
     * @param string  $cmd     Command being run
     * @param string  $arg     Rest of the message (including address)
     * @param User    $user    User sending the message
     * @param Command|bool &$result The resulting command object to be run.
     * @return bool hook value
     */
    public function onStartInterpretCommand(string $cmd, ?string $arg, User $user, &$result) : bool
    {
        $dm_cmds = ['d', 'dm'];

        if ($result === false && in_array($cmd, $dm_cmds)) {
            if (!empty($arg)) {
                list($other, $extra) = CommandInterpreter::split_arg($arg);
                if (!empty($extra)) {
                    $result = new MessageCommand($user, $other, $extra);
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Show Message button in someone's left-side navigation menu
     * 
     * @param Menu $menu
     * @param Profile $target
     * @param Profile $scoped
     * @return void
     */
    public function onEndPersonalGroupNav(Menu $menu, Profile $target, Profile $scoped = null)
    {
        if ($scoped instanceof Profile && $scoped->id == $target->id
                && !common_config('singleuser', 'enabled')) {

            $menu->out->menuItem(common_local_url('inbox', ['nickname' => $target->getNickname()]),
                                 // TRANS: Menu item in personal group navigation menu.
                                 _m('MENU','Messages'),
                                 // TRANS: Menu item title in personal group navigation menu.
                                 _('Your incoming messages'),
                                 $scoped->id === $target->id && $menu->actionName =='inbox');
        }
    }

    /**
     * Show Message button in someone's profile page
     * 
     * @param HTMLOutputter $out
     * @param Profile $profile
     * @return bool hook flag
     */
    public function onEndProfilePageActionsElements(HTMLOutputter $out, Profile $profile) : bool
    {
        $scoped = Profile::current();
        if (!$scoped instanceof Profile || $scoped->getID() === $profile->getID()) {
            return true;
        }

        if (!$profile->isLocal() && Event::handle('DirectMessageProfilePageActions', [$profile])) {
            // nothing to do if remote profile and no one to validate it
            return true;
        }

        if (!$profile->hasBlocked($scoped)) {
            $out->elementStart('li', 'entity_send-a-message');
            $out->element('a',
                        ['href' => common_local_url('newmessage', ['to' => $profile->getID()]),
                        // TRANS: Link title for link on user profile.
                        'title' => _('Send a direct message to this user.')],
                        // TRANS: Link text for link on user profile.
                        _m('BUTTON','Message'));
            $out->elementEnd('li');
        }
        
        return true;
    }

    /**
     * Notice table is used to store private messages in a newer version of the plugin,
     * this ensures we migrate entries from the old message table.
     *
     * @return bool hook flag
     */
    public function onEndUpgrade() : bool
    {
        try {
            $schema = Schema::get();
            $schema->getTableDef('message');
        } catch (SchemaTableMissingException $e) {
            return true;
        }

        $message = new Message();

        $message->selectAdd(); // clears it
        $message->selectAdd('id');
        $message->orderBy('created ASC');

        if ($message->find()) {
            while ($message->fetch()) {
                $msg = Message::getKV('id', $message->id);
                $act = $msg->asActivity();

                Notice::saveActivity($act,
                                     $msg->getFrom(),
                                     ['source' => 'web',
                                      'scope'  => NOTICE::MESSAGE_SCOPE]);
            }
        }

        $message->free();
        $message = null;

        $schema->dropTable('message');

        return true;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'Direct Message',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Mikael Nordfeldth, Bruno Casteleiro',
            'homepage' => 'https://gnu.social/',
            'rawdescription' =>
            // TRANS: Plugin description.
            _m('Direct Message to other local users.')
        ];

        return true;
    }
}
