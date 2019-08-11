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

defined('GNUSOCIAL') || die();

/**
 * SearchSub plugin main class
 *
 * @category  Plugin
 * @package   SearchSubPlugin
 * @author    Brion Vibber <brionv@status.net>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SearchSubPlugin extends Plugin
{
    const PLUGIN_VERSION = '0.1.0';

    /**
     * Database schema setup
     *
     * @return bool hook value; true means continue processing, false means stop.
     * @throws PEAR_Exception
     * @see Schema
     *
     */
    public function onCheckSchema(): bool
    {
        $schema = Schema::get();
        $schema->ensureTable('searchsub', SearchSub::schemaDef());
        return true;
    }

    /**
     * Map URLs to actions
     *
     * @param URLMapper $m path-to-action mapper
     *
     * @return bool hook value; true means continue processing, false means stop.
     * @throws Exception
     */
    public function onRouterInitialized(URLMapper $m): bool
    {
        $m->connect(
            'search/:search/subscribe',
            ['action' => 'searchsub'],
            ['search' => Router::REGEX_TAG]
        );
        $m->connect(
            'search/:search/unsubscribe',
            ['action' => 'searchunsub'],
            ['search' => Router::REGEX_TAG]
        );
        $m->connect(
            ':nickname/search-subscriptions',
            ['action' => 'searchsubs'],
            ['nickname' => Nickname::DISPLAY_FMT]
        );
        return true;
    }

    /**
     * Module version data
     *
     * @param array &$versions array of version data
     *
     * @return bool
     * @throws Exception
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'SearchSub',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Brion Vibber',
            'homepage' => 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/SearchSub',
            'rawdescription' =>
            // TRANS: Module description.
                _m('Module to allow following all messages with a given search.'));
        return true;
    }

    /**
     * Hook inbox delivery setup so search subscribers receive all
     * notices with that search in their inbox.
     *
     * Currently makes no distinction between local messages and
     * remote ones which happen to come in to the system. Remote
     * notices that don't come in at all won't ever reach this.
     *
     * @param Notice $notice
     * @param array $ni in/out map of profile IDs to inbox constants
     * @return bool hook result
     */
    public function onStartNoticeWhoGets(Notice $notice, array &$ni): bool
    {
        // Warning: this is potentially very slow
        // with a lot of searches!
        $sub = new SearchSub();
        $sub->groupBy('search');
        $sub->find();
        while ($sub->fetch()) {
            $search = $sub->search;

            if ($this->matchSearch($notice, $search)) {
                // Match? Find all those who subscribed to this
                // search term and get our delivery on...
                $searchsub = new SearchSub();
                $searchsub->search = $search;
                $searchsub->find();

                while ($searchsub->fetch()) {
                    // These constants are currently not actually used, iirc
                    $ni[$searchsub->profile_id] = NOTICE_INBOX_SOURCE_SUB;
                }
            }
        }
        return true;
    }

    /**
     * Does the given notice match the given fulltext search query?
     *
     * Warning: not guaranteed to match other search engine behavior, etc.
     * Currently using a basic case-insensitive substring match, which
     * probably fits with the 'LIKE' search but not the default MySQL
     * or Sphinx search backends.
     *
     * @param Notice $notice
     * @param string $search
     * @return bool
     */
    public function matchSearch(Notice $notice, $search): bool
    {
        return (mb_stripos($notice->content, $search) !== false);
    }

    /**
     *
     * @param NoticeSearchAction $action
     * @param string $q
     * @param Notice $notice
     * @return bool hook result
     */
    public function onStartNoticeSearchShowResults($action, $q, $notice): bool
    {
        $user = common_current_user();
        if ($user) {
            $search = $q;
            $searchsub = SearchSub::pkeyGet(array('search' => $search,
                'profile_id' => $user->id));
            if ($searchsub) {
                $form = new SearchUnsubForm($action, $search);
            } else {
                $form = new SearchSubForm($action, $search);
            }
            $action->elementStart('div', 'entity_actions');
            $action->elementStart('ul');
            $action->elementStart('li', 'entity_subscribe');
            $form->show();
            $action->elementEnd('li');
            $action->elementEnd('ul');
            $action->elementEnd('div');
        }
        return true;
    }

    /**
     * Menu item for personal subscriptions/groups area
     *
     * @param Widget $widget Widget being executed
     *
     * @return bool hook return
     * @throws Exception
     */
    public function onEndSubGroupNav($widget): bool
    {
        $action = $widget->out;
        $action_name = $action->trimmed('action');

        $action->menuItem(
            common_local_url('searchsubs', array('nickname' => $action->user->nickname)),
            // TRANS: SearchSub plugin menu item on user settings page.
            _m('MENU', 'Searches'),
            // TRANS: SearchSub plugin tooltip for user settings menu item.
            _m('Configure search subscriptions'),
            $action_name == 'searchsubs' && $action->arg('nickname') == $action->user->nickname
        );

        return true;
    }

    /**
     * Replace the built-in stub track commands with ones that control
     * search subscriptions.
     *
     * @param CommandInterpreter $cmd
     * @param string $arg
     * @param User $user
     * @param Command $result
     * @return bool hook result
     */
    public function onEndInterpretCommand($cmd, $arg, $user, &$result): bool
    {
        if ($result instanceof TrackCommand) {
            $result = new SearchSubTrackCommand($user, $arg);
            return false;
        } elseif ($result instanceof TrackOffCommand) {
            $result = new SearchSubTrackOffCommand($user);
            return false;
        } elseif ($result instanceof TrackingCommand) {
            $result = new SearchSubTrackingCommand($user);
            return false;
        } elseif ($result instanceof UntrackCommand) {
            $result = new SearchSubUntrackCommand($user, $arg);
            return false;
        } else {
            return true;
        }
    }

    public function onHelpCommandMessages($cmd, &$commands): void
    {
        // TRANS: Help message for IM/SMS command "track <word>"
        $commands["track <word>"] = _m('COMMANDHELP', "Start following notices matching the given search query.");
        // TRANS: Help message for IM/SMS command "untrack <word>"
        $commands["untrack <word>"] = _m('COMMANDHELP', "Stop following notices matching the given search query.");
        // TRANS: Help message for IM/SMS command "track off"
        $commands["track off"] = _m('COMMANDHELP', "Disable all tracked search subscriptions.");
        // TRANS: Help message for IM/SMS command "untrack all"
        $commands["untrack all"] = _m('COMMANDHELP', "Disable all tracked search subscriptions.");
        // TRANS: Help message for IM/SMS command "tracks"
        $commands["tracks"] = _m('COMMANDHELP', "List all your search subscriptions.");
        // TRANS: Help message for IM/SMS command "tracking"
        $commands["tracking"] = _m('COMMANDHELP', "List all your search subscriptions.");
    }

    public function onEndDefaultLocalNav($menu, $user): bool
    {
        $user = common_current_user();

        if (!empty($user)) {
            $searches = SearchSub::forProfile($user->getProfile());

            if (!empty($searches) && count($searches) > 0) {
                $searchSubMenu = new SearchSubMenu($menu->out, $user, $searches);
                // TRANS: Sub menu for searches.
                $menu->submenu(_m('MENU', 'Searches'), $searchSubMenu);
            }
        }

        return true;
    }
}
