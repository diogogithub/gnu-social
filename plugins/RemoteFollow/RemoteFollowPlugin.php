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
 * Remote Follow implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class RemoteFollowPlugin extends Plugin
{
    const PLUGIN_VERSION = '0.1.0';

    /**
     * Route/Reroute urls
     *
     * @param URLMapper $m
     * @return void
     */
    public function onRouterInitialized(URLMapper $m): void
    {
        // discovery
        $m->connect('main/remotefollow/nickname/:nickname',
                    ['action'   => 'RemoteFollowInit'],
                    ['nickname' => Nickname::DISPLAY_FMT]);
        $m->connect('main/remotefollow',
                    ['action' => 'RemoteFollowInit']);

        // remote follow
        $m->connect('main/remotefollowsub',
                    ['action' => 'RemoteFollowSub']);
    }

    /**
     * Add remote-follow button to someone's profile
     * 
     * @param HTMLOutputter $out
     * @param Profile $target
     * @return bool hook return value
     */
    public function onStartProfileRemoteSubscribe(HTMLOutputter $out, Profile $target): bool
    {
        if (common_logged_in() || !$target->isLocal()) {
            return true;
        }

        $out->elementStart('li', 'entity_subscribe');
        $url = common_local_url('RemoteFollowInit', ['nickname' => $target->getNickname()]);
        $out->element('a',
                      ['href'  => $url,
                       'class' => 'entity_remote_subscribe'],
                      // TRANS: Link text for the follow button
                      _m('Subscribe'));
        $out->elementEnd('li');

        return true;
    }

    /**
     * Add remote-follow button in the subscriptions list
     * 
     * @param Action $action
     * @return bool hook return value
     */
    public function onStartShowSubscriptionsContent(Action $action): bool
    {
        $this->showEntityRemoteSubscribe($action);
        return true;
    }

    /**
     * Add remote-follow button to the profile subscriptions minilist
     * 
     * @param Action $action
     * @return bool hook return value
     */
    public function onEndShowSubscriptionsMiniList(Action $action): bool
    {
        $this->showEntityRemoteSubscribe($action);
        return true;
    }

    /**
     * Add webfinger profile link for remote subscription
     */
    function onEndWebFingerProfileLinks(XML_XRD $xrd, Profile $target): bool
    {
        $xrd->links[] = new XML_XRD_Element_Link('http://ostatus.org/schema/1.0/subscribe',
                                                 common_local_url('RemoteFollowSub') . '?profile={uri}',
                                                 null, // type not set
                                                 true); // isTemplate

        return true;
    }

    /**
     * Plugin version information
     *
     * @param array $versions
     * @return bool hook return value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'RemoteFollow',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Bruno Casteleiro',
            'homepage' => 'https://notabug.org/diogo/gnu-social/src/nightly/plugins/RemoteFollow',
            // TRANS: Plugin description.
            'rawdescription' => _m('Add remote-follow button support to GNU social')
        ];
        return true;
    }

    /**
     * Add remote-follow button to some required action
     * 
     * @param Action $action
     * @return void
     */
    public function showEntityRemoteSubscribe(Action $action): void
    {
        if (!$action->getScoped() instanceof Profile) {
            // not logged in
            return;
        }

        if ($action->getScoped()->sameAs($action->getTarget())) {
            $action->elementStart('div', 'entity_actions');
            $action->elementStart('p', ['id'    => 'entity_remote_subscribe',
                                        'class' => 'entity_subscribe']);
            $action->element('a',
                             ['href'  => common_local_url('RemoteFollowSub'),
                              'class' => 'entity_remote_subscribe'],
                             // TRANS: Link text for link to remote subscribe.
                             _m('Remote'));
            $action->elementEnd('p');
            $action->elementEnd('div');
        }
    }
}
