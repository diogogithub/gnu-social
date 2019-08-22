<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * URL routing utilities
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  URL
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * URL Router
 *
 * Cheap wrapper around Net_URL_Mapper
 *
 * @category URL
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class Router
{
    var $m = null;
    static $inst = null;

    const REGEX_TAG = '[^\/]+'; // [\pL\pN_\-\.]{1,64} better if we can do unicode regexes

    static function get()
    {
        if (!Router::$inst) {
            Router::$inst = new Router();
        }
        return Router::$inst;
    }

    /**
     * Clear the global singleton instance for this class.
     * Needed to ensure reset when switching site configurations.
     */
    static function clear()
    {
        Router::$inst = null;
    }

    function __construct()
    {
        if (empty($this->m)) {
            $this->m = $this->initialize();
        }
    }

    /**
     * Create a unique hashkey for the router.
     *
     * The router's url map can change based on the version of the software
     * you're running and the plugins that are enabled. To avoid having bad routes
     * get stuck in the cache, the key includes a list of plugins and the software
     * version.
     * 
    * There can still be problems with a) differences in versions of the plugins and
     * b) people running code between official versions, but these tend to be more
     * sophisticated users who can grok what's going on and clear their caches.
     *
     * @return string cache key string that should uniquely identify a router
     */

    static function cacheKey()
    {
        $parts = array('router');

        // Many router paths depend on this setting.
        if (common_config('singleuser', 'enabled')) {
            $parts[] = '1user';
        } else {
            $parts[] = 'multi';
        }

        return Cache::codeKey(implode(':', $parts));
    }

    function initialize()
    {
        $m = new URLMapper();

        if (Event::handle('StartInitializeRouter', [&$m])) {

            // top of the menu hierarchy, sometimes "Home"
            $m->connect('', ['action' => 'top']);

            // public endpoints

            $m->connect('robots.txt', ['action' => 'robotstxt']);

            $m->connect('opensearch/people',
                        ['action' => 'opensearch',
                         'type' => 'people']);

            $m->connect('opensearch/notice',
                        ['action' => 'opensearch',
                         'type' => 'notice']);

            // docs

            $m->connect('doc/:title', ['action' => 'doc']);

            $m->connect('main/otp/:user_id/:token',
                        ['action' => 'otp'],
                        ['user_id' => '[0-9]+',
                         'token' => '.+']);

            // these take a code; before the main part

            foreach (['register', 'confirmaddress', 'recoverpassword'] as $c) {
                $m->connect('main/'.$c.'/:code', ['action' => $c]);
            }

            // Also need a block variant accepting ID on URL for mail links
            $m->connect('main/block/:profileid',
                        ['action' => 'block'],
                        ['profileid' => '[0-9]+']);

            $m->connect('main/sup/:seconds',
                        ['action' => 'sup'],
                        ['seconds' => '[0-9]+']);

            // main stuff is repetitive

            $main = ['login', 'logout', 'register', 'subscribe',
                     'unsubscribe', 'cancelsubscription', 'approvesub',
                     'confirmaddress', 'recoverpassword',
                     'invite', 'sup',
                     'block', 'unblock', 'subedit',
                     'groupblock', 'groupunblock',
                     'sandbox', 'unsandbox',
                     'silence', 'unsilence',
                     'grantrole', 'revokerole',
                     'deleteuser',
                     'geocode',
                     'version',
                     'backupaccount',
                     'deleteaccount',
                     'restoreaccount',
                     'top',
                     'public'];

            foreach ($main as $a) {
                $m->connect('main/'.$a, ['action' => $a]);
            }

            $m->connect('main/all', ['action' => 'networkpublic']);

            $m->connect('main/tagprofile/:id',
                        ['action' => 'tagprofile'],
                        ['id' => '[0-9]+']);

            $m->connect('main/tagprofile', ['action' => 'tagprofile']);

            $m->connect('main/xrds',
                        ['action' => 'publicxrds']);

            // settings

            foreach (['profile', 'avatar', 'password', 'im', 'oauthconnections',
                           'oauthapps', 'email', 'sms', 'url'] as $s) {
                $m->connect('settings/'.$s, ['action' => $s.'settings']);
            }

            if (common_config('oldschool', 'enabled')) {
                $m->connect('settings/oldschool', ['action' => 'oldschoolsettings']);
            }

            $m->connect('settings/oauthapps/show/:id',
                        ['action' => 'showapplication'],
                        ['id' => '[0-9]+']);

            $m->connect('settings/oauthapps/new',
                        ['action' => 'newapplication']);

            $m->connect('settings/oauthapps/edit/:id',
                        ['action' => 'editapplication'],
                        ['id' => '[0-9]+']);

            $m->connect('settings/oauthapps/delete/:id',
                        ['action' => 'deleteapplication'],
                        ['id' => '[0-9]+']);

            // search

            foreach (['group', 'people', 'notice'] as $s) {
                $m->connect('search/'.$s.'?q=:q',
                            ['action' => $s.'search'],
                            ['q' => '.+']);
                $m->connect('search/'.$s, ['action' => $s.'search']);
            }

            // The second of these is needed to make the link work correctly
            // when inserted into the page. The first is needed to match the
            // route on the way in. Seems to be another Net_URL_Mapper bug to me.
            $m->connect('search/notice/rss?q=:q',
                        ['action' => 'noticesearchrss'],
                        ['q' => '.+']);
            $m->connect('search/notice/rss', ['action' => 'noticesearchrss']);

            foreach (['' => 'attachment',
                      '/view' => 'attachment_view',
                      '/download' => 'attachment_download',
                      '/thumbnail' => 'attachment_thumbnail'] as $postfix => $action) {
                foreach (['filehash' => '[A-Za-z0-9._-]{64}',
                          'attachment' => '[0-9]+'] as $type => $match) {
                    $m->connect("attachment/:{$type}{$postfix}",
                                ['action' => $action],
                                [$type => $match]);
                }
            }

            $m->connect('notice/new?replyto=:replyto&inreplyto=:inreplyto',
                        ['action' => 'newnotice'],
                        ['replyto' => Nickname::DISPLAY_FMT,
                         'inreplyto' => '[0-9]+']);

            $m->connect('notice/new?replyto=:replyto',
                        ['action' => 'newnotice'],
                        ['replyto' => Nickname::DISPLAY_FMT]);

            $m->connect('notice/new', ['action' => 'newnotice']);

            $m->connect('notice/:notice',
                        ['action' => 'shownotice'],
                        ['notice' => '[0-9]+']);

            $m->connect('notice/:notice/delete',
                        ['action' => 'deletenotice'],
                        ['notice' => '[0-9]+']);

            // conversation

            $m->connect('conversation/:id',
                        ['action' => 'conversation'],
                        ['id' => '[0-9]+']);

            $m->connect('user/:id',
                        ['action' => 'userbyid'],
                        ['id' => '[0-9]+']);

            $m->connect('tag/:tag/rss',
                        ['action' => 'tagrss'],
                        ['tag' => self::REGEX_TAG]);
            $m->connect('tag/:tag',
                        ['action' => 'tag'],
                        ['tag' => self::REGEX_TAG]);

            // groups

            $m->connect('group/new', ['action' => 'newgroup']);

            foreach (['edit', 'join', 'leave', 'delete', 'cancel', 'approve'] as $v) {
                $m->connect('group/:nickname/'.$v,
                            ['action' => $v.'group'],
                            ['nickname' => Nickname::DISPLAY_FMT]);
                $m->connect('group/:id/id/'.$v,
                            ['action' => $v.'group'],
                            ['id' => '[0-9]+']);
            }

            foreach (['members', 'logo', 'rss'] as $n) {
                $m->connect('group/:nickname/'.$n,
                            ['action' => 'group'.$n],
                            ['nickname' => Nickname::DISPLAY_FMT]);
            }

            $m->connect('group/:nickname/foaf',
                        ['action' => 'foafgroup'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect('group/:nickname/blocked',
                        ['action' => 'blockedfromgroup'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect('group/:nickname/makeadmin',
                        ['action' => 'makeadmin'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect('group/:nickname/members/pending',
                        ['action' => 'groupqueue'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect('group/:id/id',
                        ['action' => 'groupbyid'],
                        ['id' => '[0-9]+']);

            $m->connect('group/:nickname',
                        ['action' => 'showgroup'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect('group/:nickname/',
                        ['action' => 'showgroup'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect('group/', ['action' => 'groups']);
            $m->connect('group', ['action' => 'groups']);
            $m->connect('groups/', ['action' => 'groups']);
            $m->connect('groups', ['action' => 'groups']);

            // Twitter-compatible API

            // statuses API

            $m->connect('api',
                        ['action' => 'Redirect',
                         'nextAction' => 'doc',
                         'args' => ['title' => 'api']]);

            $m->connect('api/statuses/public_timeline.:format',
                        ['action' => 'ApiTimelinePublic'],
                        ['format' => '(xml|json|rss|atom|as)']);

            // this is not part of the Twitter API. Also may require authentication depending on server config!
            $m->connect('api/statuses/networkpublic_timeline.:format',
                        ['action' => 'ApiTimelineNetworkPublic'],
                        ['format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/friends_timeline/:id.:format',
                        ['action' => 'ApiTimelineFriends'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/friends_timeline.:format',
                        ['action' => 'ApiTimelineFriends'],
                        ['format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/home_timeline/:id.:format',
                        ['action' => 'ApiTimelineHome'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/home_timeline.:format',
                        ['action' => 'ApiTimelineHome'],
                        ['format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/user_timeline/:id.:format',
                        ['action' => 'ApiTimelineUser'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/user_timeline.:format',
                        ['action' => 'ApiTimelineUser'],
                        ['format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/mentions/:id.:format',
                        ['action' => 'ApiTimelineMentions'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/mentions.:format',
                        ['action' => 'ApiTimelineMentions'],
                        ['format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/replies/:id.:format',
                        ['action' => 'ApiTimelineMentions'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/replies.:format',
                        ['action' => 'ApiTimelineMentions'],
                        ['format' => '(xml|json|rss|atom|as)']);
 
            $m->connect('api/statuses/mentions_timeline/:id.:format',
                        ['action' => 'ApiTimelineMentions'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/mentions_timeline.:format',
                        ['action' => 'ApiTimelineMentions'],
                        ['format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statuses/friends/:id.:format',
                        ['action' => 'ApiUserFriends'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/statuses/friends.:format',
                        ['action' => 'ApiUserFriends'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statuses/followers/:id.:format',
                        ['action' => 'ApiUserFollowers'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/statuses/followers.:format',
                        ['action' => 'ApiUserFollowers'],
                         ['format' => '(xml|json)']);

            $m->connect('api/statuses/show/:id.:format',
                        ['action' => 'ApiStatusesShow'],
                        ['id' => '[0-9]+',
                         'format' => '(xml|json|atom)']);

            $m->connect('api/statuses/show.:format',
                        ['action' => 'ApiStatusesShow'],
                        ['format' => '(xml|json|atom)']);

            $m->connect('api/statuses/update.:format',
                        ['action' => 'ApiStatusesUpdate'],
                        ['format' => '(xml|json|atom)']);

            $m->connect('api/statuses/destroy/:id.:format',
                        ['action' => 'ApiStatusesDestroy'],
                        ['id' => '[0-9]+',
                         'format' => '(xml|json)']);

            $m->connect('api/statuses/destroy.:format',
                        ['action' => 'ApiStatusesDestroy'],
                        ['format' => '(xml|json)']);

            // START qvitter API additions
            
            $m->connect('api/attachment/:id.:format',
                        ['action' => 'ApiAttachment'],
                        ['id' => '[0-9]+',
                         'format' => '(xml|json)']);
            
            $m->connect('api/checkhub.:format',
                        ['action' => 'ApiCheckHub'],
                        ['format' => '(xml|json)']);
            
            $m->connect('api/externalprofile/show.:format',
                        ['action' => 'ApiExternalProfileShow'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/admins/:id.:format',
                        ['action' => 'ApiGroupAdmins'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);
            
            $m->connect('api/account/update_link_color.:format',
                        ['action' => 'ApiAccountUpdateLinkColor'],
                        ['format' => '(xml|json)']);
                
            $m->connect('api/account/update_background_color.:format',
                        ['action' => 'ApiAccountUpdateBackgroundColor'],
                        ['format' => '(xml|json)']);

            $m->connect('api/account/register.:format',
                        ['action' => 'ApiAccountRegister'],
                        ['format' => '(xml|json)']);
            
            $m->connect('api/check_nickname.:format',
                        ['action' => 'ApiCheckNickname'],
                        ['format' => '(xml|json)']);

            // END qvitter API additions

            // users

            $m->connect('api/users/show/:id.:format',
                        ['action' => 'ApiUserShow'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/users/show.:format',
                        ['action' => 'ApiUserShow'],
                        ['format' => '(xml|json)']);

            $m->connect('api/users/profile_image/:screen_name.:format',
                        ['action' => 'ApiUserProfileImage'],
                        ['screen_name' => Nickname::DISPLAY_FMT,
                         'format' => '(xml|json)']);

            // friendships

            $m->connect('api/friendships/show.:format',
                        ['action' => 'ApiFriendshipsShow'],
                        ['format' => '(xml|json)']);

            $m->connect('api/friendships/exists.:format',
                        ['action' => 'ApiFriendshipsExists'],
                        ['format' => '(xml|json)']);

            $m->connect('api/friendships/create/:id.:format',
                        ['action' => 'ApiFriendshipsCreate'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/friendships/create.:format',
                        ['action' => 'ApiFriendshipsCreate'],
                        ['format' => '(xml|json)']);

            $m->connect('api/friendships/destroy/:id.:format',
                        ['action' => 'ApiFriendshipsDestroy'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/friendships/destroy.:format',
                        ['action' => 'ApiFriendshipsDestroy'],
                        ['format' => '(xml|json)']);

            // Social graph

            $m->connect('api/friends/ids/:id.:format',
                        ['action' => 'ApiUserFriends',
                         'ids_only' => true],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/followers/ids/:id.:format',
                        ['action' => 'ApiUserFollowers',
                         'ids_only' => true],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/friends/ids.:format',
                        ['action' => 'ApiUserFriends',
                         'ids_only' => true],
                        ['format' => '(xml|json)']);

            $m->connect('api/followers/ids.:format',
                        ['action' => 'ApiUserFollowers',
                         'ids_only' => true],
                        ['format' => '(xml|json)']);

            // account

            $m->connect('api/account/verify_credentials.:format',
                        ['action' => 'ApiAccountVerifyCredentials'],
                        ['format' => '(xml|json)']);

            $m->connect('api/account/update_profile.:format',
                        ['action' => 'ApiAccountUpdateProfile'],
                        ['format' => '(xml|json)']);

            $m->connect('api/account/update_profile_image.:format',
                        ['action' => 'ApiAccountUpdateProfileImage'],
                        ['format' => '(xml|json)']);

            $m->connect('api/account/update_delivery_device.:format',
                        ['action' => 'ApiAccountUpdateDeliveryDevice'],
                        ['format' => '(xml|json)']);

            // special case where verify_credentials is called w/out a format

            $m->connect('api/account/verify_credentials',
                        ['action' => 'ApiAccountVerifyCredentials']);

            $m->connect('api/account/rate_limit_status.:format',
                        ['action' => 'ApiAccountRateLimitStatus'],
                        ['format' => '(xml|json)']);

            // blocks

            $m->connect('api/blocks/create/:id.:format',
                        ['action' => 'ApiBlockCreate'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/blocks/create.:format',
                        ['action' => 'ApiBlockCreate'],
                        ['format' => '(xml|json)']);

            $m->connect('api/blocks/destroy/:id.:format',
                        ['action' => 'ApiBlockDestroy'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/blocks/destroy.:format',
                        ['action' => 'ApiBlockDestroy'],
                        ['format' => '(xml|json)']);

            // help

            $m->connect('api/help/test.:format',
                        ['action' => 'ApiHelpTest'],
                        ['format' => '(xml|json)']);

            // statusnet

            $m->connect('api/statusnet/version.:format',
                        ['action' => 'ApiGNUsocialVersion'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/config.:format',
                        ['action' => 'ApiGNUsocialConfig'],
                        ['format' => '(xml|json)']);

            // For our current software name, we provide "gnusocial" base action

            $m->connect('api/gnusocial/version.:format',
                        ['action' => 'ApiGNUsocialVersion'],
                        ['format' => '(xml|json)']);

            $m->connect('api/gnusocial/config.:format',
                        ['action' => 'ApiGNUsocialConfig'],
                        ['format' => '(xml|json)']);

            // Groups and tags are newer than 0.8.1 so no backward-compatibility
            // necessary

            // Groups
            //'list' has to be handled differently, as php will not allow a method to be named 'list'

            $m->connect('api/statusnet/groups/timeline/:id.:format',
                        ['action' => 'ApiTimelineGroup'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom|as)']);

            $m->connect('api/statusnet/groups/show/:id.:format',
                        ['action' => 'ApiGroupShow'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/show.:format',
                        ['action' => 'ApiGroupShow'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/join/:id.:format',
                        ['action' => 'ApiGroupJoin'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/join.:format',
                        ['action' => 'ApiGroupJoin'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/leave/:id.:format',
                        ['action' => 'ApiGroupLeave'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/leave.:format',
                        ['action' => 'ApiGroupLeave'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/is_member.:format',
                        ['action' => 'ApiGroupIsMember'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/list/:id.:format',
                        ['action' => 'ApiGroupList'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json|rss|atom)']);

            $m->connect('api/statusnet/groups/list.:format',
                        ['action' => 'ApiGroupList'],
                        ['format' => '(xml|json|rss|atom)']);

            $m->connect('api/statusnet/groups/list_all.:format',
                        ['action' => 'ApiGroupListAll'],
                        ['format' => '(xml|json|rss|atom)']);

            $m->connect('api/statusnet/groups/membership/:id.:format',
                        ['action' => 'ApiGroupMembership'],
                        ['id' => Nickname::INPUT_FMT,
                         'format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/membership.:format',
                        ['action' => 'ApiGroupMembership'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/create.:format',
                        ['action' => 'ApiGroupCreate'],
                        ['format' => '(xml|json)']);

            $m->connect('api/statusnet/groups/update/:id.:format',
                        ['action' => 'ApiGroupProfileUpdate'],
                        ['id' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);
                              
            $m->connect('api/statusnet/conversation/:id.:format',
                        ['action' => 'apiconversation'],
                        ['id' => '[0-9]+',
                         'format' => '(xml|json|rss|atom|as)']);

            // Lists (people tags)
            $m->connect('api/lists/list.:format',
                        ['action' => 'ApiListSubscriptions'],
                        ['format' => '(xml|json)']);

            $m->connect('api/lists/memberships.:format',
                        ['action' => 'ApiListMemberships'],
                        ['format' => '(xml|json)']);

            $m->connect('api/:user/lists/memberships.:format',
                        ['action' => 'ApiListMemberships'],
                        ['user' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);

            $m->connect('api/lists/subscriptions.:format',
                        ['action' => 'ApiListSubscriptions'],
                        ['format' => '(xml|json)']);

            $m->connect('api/:user/lists/subscriptions.:format',
                        ['action' => 'ApiListSubscriptions'],
                        ['user' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);

            $m->connect('api/lists.:format',
                        ['action' => 'ApiLists'],
                        ['format' => '(xml|json)']);

            $m->connect('api/:user/lists/:id.:format',
                        ['action' => 'ApiList'],
                        ['user' => '[a-zA-Z0-9]+',
                         'id' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);

            $m->connect('api/:user/lists.:format',
                        ['action' => 'ApiLists'],
                        ['user' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);

            $m->connect('api/:user/lists/:id/statuses.:format',
                        ['action' => 'ApiTimelineList'],
                        ['user' => '[a-zA-Z0-9]+',
                         'id' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json|rss|atom)']);

            $m->connect('api/:user/:list_id/members/:id.:format',
                        ['action' => 'ApiListMember'],
                        ['user' => '[a-zA-Z0-9]+',
                         'list_id' => '[a-zA-Z0-9]+',
                         'id' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);

            $m->connect('api/:user/:list_id/members.:format',
                        ['action' => 'ApiListMembers'],
                        ['user' => '[a-zA-Z0-9]+',
                        'list_id' => '[a-zA-Z0-9]+',
                        'format' => '(xml|json)']);

            $m->connect('api/:user/:list_id/subscribers/:id.:format',
                        ['action' => 'ApiListSubscriber'],
                        ['user' => '[a-zA-Z0-9]+',
                         'list_id' => '[a-zA-Z0-9]+',
                         'id' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);

            $m->connect('api/:user/:list_id/subscribers.:format',
                        ['action' => 'ApiListSubscribers'],
                        ['user' => '[a-zA-Z0-9]+',
                         'list_id' => '[a-zA-Z0-9]+',
                         'format' => '(xml|json)']);

            // Tags
            $m->connect('api/statusnet/tags/timeline/:tag.:format',
                        ['action' => 'ApiTimelineTag'],
                        ['tag'    => self::REGEX_TAG,
                         'format' => '(xml|json|rss|atom|as)']);

            // media related
            $m->connect('api/statusnet/media/upload',
                        ['action' => 'ApiMediaUpload']);

            $m->connect('api/statuses/update_with_media.json',
                        ['action' => 'ApiMediaUpload']);

            // Twitter Media upload API v1.1
            $m->connect('api/media/upload.:format',
                        ['action' => 'ApiMediaUpload'],
                        ['format' => '(xml|json)']);

            // search
            $m->connect('api/search.atom', ['action' => 'ApiSearchAtom']);
            $m->connect('api/search.json', ['action' => 'ApiSearchJSON']);
            $m->connect('api/trends.json', ['action' => 'ApiTrends']);

            $m->connect('api/oauth/request_token',
                        ['action' => 'ApiOAuthRequestToken']);

            $m->connect('api/oauth/access_token',
                        ['action' => 'ApiOAuthAccessToken']);

            $m->connect('api/oauth/authorize',
                        ['action' => 'ApiOAuthAuthorize']);

            // Admin

            $m->connect('panel/site', ['action' => 'siteadminpanel']);
            $m->connect('panel/user', ['action' => 'useradminpanel']);
            $m->connect('panel/access', ['action' => 'accessadminpanel']);
            $m->connect('panel/paths', ['action' => 'pathsadminpanel']);
            $m->connect('panel/sessions', ['action' => 'sessionsadminpanel']);
            $m->connect('panel/sitenotice', ['action' => 'sitenoticeadminpanel']);
            $m->connect('panel/license', ['action' => 'licenseadminpanel']);

            $m->connect('panel/plugins', ['action' => 'pluginsadminpanel']);
            $m->connect('panel/plugins/enable/:plugin',
                        ['action' => 'pluginenable'],
                        ['plugin' => '[A-Za-z0-9_]+']);
            $m->connect('panel/plugins/disable/:plugin',
                        ['action' => 'plugindisable'],
                        ['plugin' => '[A-Za-z0-9_]+']);
            $m->connect('panel/plugins/delete/:plugin',
                ['action' => 'plugindelete'],
                ['plugin' => '[A-Za-z0-9_]+']);
            $m->connect('panel/plugins/install',
                        ['action' => 'plugininstall']);

            // Common people-tag stuff

            $m->connect('peopletag/:tag',
                        ['action' => 'peopletag'],
                        ['tag'    => self::REGEX_TAG]);

            $m->connect('selftag/:tag',
                        ['action' => 'selftag'],
                        ['tag'    => self::REGEX_TAG]);

            $m->connect('main/addpeopletag', ['action' => 'addpeopletag']);

            $m->connect('main/removepeopletag', ['action' => 'removepeopletag']);

            $m->connect('main/profilecompletion', ['action' => 'profilecompletion']);

            $m->connect('main/peopletagautocomplete', ['action' => 'peopletagautocomplete']);

            // In the "root"

            if (common_config('singleuser', 'enabled')) {

                $nickname = User::singleUserNickname();

                foreach (['subscriptions', 'subscribers', 'all', 'foaf', 'replies'] as $a) {
                    $m->connect($a,
                                ['action' => $a,
                                 'nickname' => $nickname]);
                }

                foreach (['subscriptions', 'subscribers'] as $a) {
                    $m->connect($a.'/:tag',
                                ['action' => $a,
                                 'nickname' => $nickname],
                                ['tag' => self::REGEX_TAG]);
                }

                $m->connect('subscribers/pending',
                            ['action' => 'subqueue',
                             'nickname' => $nickname]);

                foreach (['rss', 'groups'] as $a) {
                    $m->connect($a,
                                ['action' => 'user'.$a,
                                 'nickname' => $nickname]);
                }

                foreach (['all', 'replies'] as $a) {
                    $m->connect($a.'/rss',
                                ['action' => $a.'rss',
                                 'nickname' => $nickname]);
                }

                $m->connect('avatar',
                            ['action' => 'avatarbynickname',
                             'nickname' => $nickname]);

                $m->connect('avatar/:size',
                            ['action' => 'avatarbynickname',
                             'nickname' => $nickname],
                            ['size' => '(|original|\d+)']);

                $m->connect('tag/:tag/rss',
                            ['action' => 'userrss',
                             'nickname' => $nickname],
                            ['tag' => self::REGEX_TAG]);

                $m->connect('tag/:tag',
                            ['action' => 'showstream',
                             'nickname' => $nickname],
                            ['tag' => self::REGEX_TAG]);

                $m->connect('rsd.xml',
                            ['action' => 'rsd',
                             'nickname' => $nickname]);

                // peopletags

                $m->connect('peopletags',
                            ['action' => 'peopletagsbyuser']);

                $m->connect('peopletags/private',
                            ['action' => 'peopletagsbyuser',
                             'private' => 1]);

                $m->connect('peopletags/public',
                            ['action' => 'peopletagsbyuser',
                             'public' => 1]);

                $m->connect('othertags',
                            ['action' => 'peopletagsforuser']);

                $m->connect('peopletagsubscriptions',
                            ['action' => 'peopletagsubscriptions']);

                $m->connect('all/:tag/subscribers',
                            ['action' => 'peopletagsubscribers'],
                            ['tag' => self::REGEX_TAG]);

                $m->connect('all/:tag/tagged',
                            ['action' => 'peopletagged'],
                            ['tag' => self::REGEX_TAG]);

                $m->connect('all/:tag/edit',
                            ['action' => 'editpeopletag'],
                            ['tag' => self::REGEX_TAG]);

                foreach (['subscribe', 'unsubscribe'] as $v) {
                    $m->connect('peopletag/:id/'.$v,
                                ['action' => $v.'peopletag'],
                                ['id' => '[0-9]{1,64}']);
                }

                $m->connect('user/:tagger_id/profiletag/:id/id',
                            ['action' => 'profiletagbyid'],
                            ['tagger_id' => '[0-9]+',
                             'id' => '[0-9]+']);

                $m->connect('all/:tag',
                            ['action' => 'showprofiletag',
                             'tagger' => $nickname],
                            ['tag' => self::REGEX_TAG]);

                foreach (['subscriptions', 'subscribers'] as $a) {
                    $m->connect($a.'/:tag',
                                ['action' => $a],
                                ['tag' => self::REGEX_TAG]);
                }
            }

            $m->connect('rss', ['action' => 'publicrss']);
            $m->connect('featuredrss', ['action' => 'featuredrss']);
            $m->connect('featured/', ['action' => 'featured']);
            $m->connect('featured', ['action' => 'featured']);
            $m->connect('rsd.xml', ['action' => 'rsd']);

            foreach (['subscriptions', 'subscribers',
                           'nudge', 'all', 'foaf', 'replies',
                           'inbox', 'outbox'] as $a) {
                $m->connect(':nickname/'.$a,
                            ['action' => $a],
                            ['nickname' => Nickname::DISPLAY_FMT]);
            }
            
            $m->connect(':nickname/subscribers/pending',
                        ['action' => 'subqueue'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            // some targeted RSS 1.0 actions (extends TargetedRss10Action)
            foreach (['all', 'replies'] as $a) {
                $m->connect(':nickname/'.$a.'/rss',
                            ['action' => $a.'rss'],
                            ['nickname' => Nickname::DISPLAY_FMT]);
            }

            // people tags

            $m->connect(':nickname/peopletags',
                        ['action' => 'peopletagsbyuser'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':nickname/peopletags/private',
                        ['action' => 'peopletagsbyuser',
                         'private' => 1],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':nickname/peopletags/public',
                        ['action' => 'peopletagsbyuser',
                         'public' => 1],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':nickname/othertags',
                        ['action' => 'peopletagsforuser'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':nickname/peopletagsubscriptions',
                        ['action' => 'peopletagsubscriptions'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':tagger/all/:tag/subscribers',
                        ['action' => 'peopletagsubscribers'],
                        ['tagger' => Nickname::DISPLAY_FMT,
                         'tag' => self::REGEX_TAG]);

            $m->connect(':tagger/all/:tag/tagged',
                        ['action' => 'peopletagged'],
                        ['tagger' => Nickname::DISPLAY_FMT,
                         'tag' => self::REGEX_TAG]);

            $m->connect(':tagger/all/:tag/edit',
                        ['action' => 'editpeopletag'],
                        ['tagger' => Nickname::DISPLAY_FMT,
                         'tag' => self::REGEX_TAG]);

            foreach (['subscribe', 'unsubscribe'] as $v) {
                $m->connect('peopletag/:id/'.$v,
                            ['action' => $v.'peopletag'],
                            ['id' => '[0-9]{1,64}']);
            }
            
            $m->connect('user/:tagger_id/profiletag/:id/id',
                        ['action' => 'profiletagbyid'],
                        ['tagger_id' => '[0-9]+',
                         'id' => '[0-9]+']);

            $m->connect(':nickname/all/:tag',
                        ['action' => 'showprofiletag'],
                        ['nickname' => Nickname::DISPLAY_FMT,
                         'tag' => self::REGEX_TAG]);

            foreach (['subscriptions', 'subscribers'] as $a) {
                $m->connect(':nickname/'.$a.'/:tag',
                            ['action' => $a],
                            ['tag' => self::REGEX_TAG,
                             'nickname' => Nickname::DISPLAY_FMT]);
            }

            foreach (['rss', 'groups'] as $a) {
                $m->connect(':nickname/'.$a,
                            ['action' => 'user'.$a],
                            ['nickname' => Nickname::DISPLAY_FMT]);
            }

            $m->connect(':nickname/avatar',
                        ['action' => 'avatarbynickname'],
                        ['nickname' => Nickname::DISPLAY_FMT]);
            
            $m->connect(':nickname/avatar/:size',
                        ['action' => 'avatarbynickname'],
                        ['size' => '(|original|\d+)',
                         'nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':nickname/tag/:tag/rss',
                        ['action' => 'userrss'],
                        ['nickname' => Nickname::DISPLAY_FMT,
                         'tag' => self::REGEX_TAG]);

            $m->connect(':nickname/tag/:tag',
                        ['action' => 'showstream'],
                        ['nickname' => Nickname::DISPLAY_FMT,
                         'tag' => self::REGEX_TAG]);

            $m->connect(':nickname/rsd.xml',
                        ['action' => 'rsd'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':nickname',
                        ['action' => 'showstream'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            $m->connect(':nickname/',
                        ['action' => 'showstream'],
                        ['nickname' => Nickname::DISPLAY_FMT]);

            // AtomPub API

            $m->connect('api/statusnet/app/service/:id.xml',
                        ['action' => 'ApiAtomService'],
                        ['id' => Nickname::DISPLAY_FMT]);

            $m->connect('api/statusnet/app/service.xml',
                        ['action' => 'ApiAtomService']);

            $m->connect('api/statusnet/app/subscriptions/:subscriber/:subscribed.atom',
                        ['action' => 'AtomPubShowSubscription'],
                        ['subscriber' => '[0-9]+',
                         'subscribed' => '[0-9]+']);

            $m->connect('api/statusnet/app/subscriptions/:subscriber.atom',
                        ['action' => 'AtomPubSubscriptionFeed'],
                        ['subscriber' => '[0-9]+']);

            $m->connect('api/statusnet/app/memberships/:profile/:group.atom',
                        ['action' => 'AtomPubShowMembership'],
                        ['profile' => '[0-9]+',
                         'group' => '[0-9]+']);

            $m->connect('api/statusnet/app/memberships/:profile.atom',
                        ['action' => 'AtomPubMembershipFeed'],
                        ['profile' => '[0-9]+']);

            // URL shortening

            $m->connect('url/:id',
                        ['action' => 'redirecturl'],
                        ['id' => '[0-9]+']);

            // user stuff

            Event::handle('RouterInitialized', [$m]);
        }

        return $m;
    }

    function map($path)
    {
        try {
            return $this->m->match($path);
        } catch (NoRouteMapException $e) {
            common_debug($e->getMessage());
            // TRANS: Client error on action trying to visit a non-existing page.
            throw new ClientException(_('Page not found.'), 404);
        }
    }

    function build($action, $args=null, $params=null, $fragment=null)
    {
        $action_arg = array('action' => $action);

        if ($args) {
            $args = array_merge($action_arg, $args);
        } else {
            $args = $action_arg;
        }

        $url = $this->m->generate($args, $params, $fragment);
        // Due to a bug in the Net_URL_Mapper code, the returned URL may
        // contain a malformed query of the form ?p1=v1?p2=v2?p3=v3. We
        // repair that here rather than modifying the upstream code...

        $qpos = strpos($url, '?');
        if ($qpos !== false) {
            $url = substr($url, 0, $qpos+1) .
                str_replace('?', '&', substr($url, $qpos+1));

            // @fixme this is a hacky workaround for http_build_query in the
            // lower-level code and bad configs that set the default separator
            // to &amp; instead of &. Encoded &s in parameters will not be
            // affected.
            $url = substr($url, 0, $qpos+1) .
                str_replace('&amp;', '&', substr($url, $qpos+1));

        }

        return $url;
    }
}
