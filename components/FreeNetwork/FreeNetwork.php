<?php

declare(strict_types = 1);

// {{{ License
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
// }}}

namespace Component\FreeNetwork;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Router\Router;
use Component\FreeNetwork\Entity\FreenetworkActor;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchActorException;
use App\Util\Exception\ServerException;
use App\Util\Nickname;
use Component\FreeNetwork\Controller\HostMeta;
use Component\FreeNetwork\Controller\OwnerXrd;
use Component\FreeNetwork\Controller\Webfinger;
use Component\FreeNetwork\Util\Discovery;
use Component\FreeNetwork\Util\WebfingerResource;
use Component\FreeNetwork\Util\WebfingerResource\WebfingerResourceActor;
use Doctrine\ORM\NoResultException;
use Exception;
use Plugin\ActivityPub\Entity\ActivitypubActivity;
use Plugin\ActivityPub\Util\Response\TypeResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use XML_XRD_Element_Link;

/**
 * Implements WebFinger (RFC7033) for GNU social, as well as Link-based Resource Descriptor Discovery based on RFC6415,
 * Web Host Metadata ('.well-known/host-meta') resource.
 *
 * @package GNUsocial
 *
 * @author  Mikael Nordfeldth <mmn@hethane.se>
 * @author  Diogo Peralta Cordeiro <mail@diogo.site>
 */
class FreeNetwork extends Component
{
    const PLUGIN_VERSION = '0.1.0';

    const OAUTH_ACCESS_TOKEN_REL  = 'http://apinamespace.org/oauth/access_token';
    const OAUTH_REQUEST_TOKEN_REL = 'http://apinamespace.org/oauth/request_token';
    const OAUTH_AUTHORIZE_REL     = 'http://apinamespace.org/oauth/authorize';

    public function onAddRoute(RouteLoader $m): bool
    {
        $m->connect('freenetwork_hostmeta', '.well-known/host-meta', [HostMeta::class, 'handle']);
        $m->connect('freenetwork_hostmeta_format', '.well-known/host-meta.:format',
            [HostMeta::class, 'handle'],
            ['format' => '(xml|json)']);
        // the resource GET parameter can be anywhere, so don't mention it here
        $m->connect('freenetwork_webfinger', '.well-known/webfinger', [Webfinger::class, 'handle']);
        $m->connect('freenetwork_webfinger_format', '.well-known/webfinger.:format',
            [Webfinger::class, 'handle'],
            ['format' => '(xml|json)']);
        $m->connect('freenetwork_ownerxrd', 'main/ownerxrd', [OwnerXrd::class, 'handle']);
        return Event::next;
    }

    public function onLoginAction($action, &$login)
    {
        switch ($action) {
            case 'hostmeta':
            case 'webfinger':
                $login = true;
                return false;
        }

        return true;
    }

    /**
     * @param Actor $actor
     * @param LocalUser $user
     * @return bool
     */
//    public function onSuccessfulLocalUserRegistration(Actor $actor, LocalUser $user): bool
//    {
//        //$profile_page = Router::url(id: 'actor_view_nickname', args: ['nickname' => $actor->getNickname()], type: Router::ABSOLUTE_URL);
//        $profile_page = $actor->getUrl(Router::ABSOLUTE_URL);
//        $actor_uri = $actor->getUri(Router::ABSOLUTE_URL);
//        Event::handle('FreeNetworkSaveProfilePage', [$source = 'user', $actor_id = $actor->getId(), &$profile_page, &$actor_uri]);
//        $fnactorpp = FreenetworkActor::create([
//            'actor_uri' => $profile_page,
//            'source'    => $source,
//            'actor_id'  => $actor_id,
//            'is_local'  => true,
//        ]);
//        DB::persist($fnactorpp);
//        if ($profile_page !== $actor_uri) {
//            $fnactoruri = FreenetworkActor::create([
//                'actor_uri' => $actor_uri,
//                'source' => $source,
//                'actor_id' => $actor_id,
//                'is_local' => true,
//            ]);
//            DB::persist($fnactoruri);
//        }
//        return Event::next;
//    }

    public function onStartGetProfileAcctUri(Actor $profile, &$acct)
    {
        $wfr = new WebFingerResourceActor($profile);
        try {
            $acct = $wfr->reconstructAcct();
        } catch (Exception $e) {
            return true;
        }

        return false;
    }

    public function onEndGetWebFingerResource($resource, WebfingerResource &$target = null, array $args = [])
    {
        // * Either we didn't find the profile, then we want to make
        //   the $profile variable null for clarity.
        // * Or we did find it but for a possibly malicious remote
        //   user who might've set their profile URL to a Note URL
        //   which would've caused a sort of DoS unless we continue
        //   our search here by discarding the remote profile.
        $profile = null;
        if (Discovery::isAcct($resource)) {
            $parts = explode('@', substr(urldecode($resource), 5)); // 5 is strlen of 'acct:'
            if (count($parts) == 2) {
                list($nick, $domain) = $parts;
                if ($domain !== $_ENV['SOCIAL_DOMAIN']) {// XXX: Common::config('site', 'server')) {
                    throw new ServerException(_m('Remote profiles not supported via WebFinger yet.'));
                }

                $nick = Nickname::normalize(nickname: $nick, check_already_used: false, check_is_allowed: false);
                $freenetwork_actor = LocalUser::getWithPK(['nickname' => $nick]);
                if (!($freenetwork_actor instanceof LocalUser)) {
                    throw new NoSuchActorException($nick);
                }
                $profile = $freenetwork_actor->getActor();
            }
        } elseif (!filter_var($resource, FILTER_VALIDATE_URL)) {
            // Try the User URI lookup!
            try {
                $resource_parts = parse_url($resource);
                if ($resource_parts['host'] === $_ENV['SOCIAL_DOMAIN']) {
                    $str = parse_url($resource_parts['path']);
                    // actor_view_nickname
                    $renick = '/\/@(' . Nickname::DISPLAY_FMT . ')\/?/m';
                    // actor_view_id
                    $reuri = '/\/actor/(\d+)\/?/m';
                    if (preg_match_all($renick, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                        $profile = LocalUser::getWithPK(['nickname' => $matches[1]])->getActor();
                    } else if (preg_match_all($reuri, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                        $profile = Actor::getById($matches[1]);
                    }
                } else {
                    throw new NoResultException();
                }
            } catch (NoResultException $e) {
                // not a User, maybe a Note? we'll try that further down...
            }
        } else {
            // this means $resource is a common_valid_http_url (or https)
            // First build up a set of alternative resource URLs that we can use.
            try {
                Log::debug(__METHOD__ . ': Finding User URI for WebFinger lookup on resource==' . $resource);
                $freenetwork_actor = FreenetworkActor::getWithPK(['profile_page' => $resource]);
                if ($freenetwork_actor !== null) {
                    $profile = Actor::getById($freenetwork_actor->getActorId());
                }
                unset($freenetwork_actor);
            } catch (Exception $e) {
                // Most likely a UserNoProfileException, if it ever happens
                // and then we need to do some debugging and perhaps fixes.
                Log::error(get_class($e) . ': ' . $e->getMessage());
                throw $e;
            }

//            try {
//                Log::debug(__METHOD__ . ': Finding User_group URI for WebFinger lookup on resource==' . $resource);
//                $group = new User_group();
//                $group->whereAddIn('uri', array_keys($alt_urls), $group->columnType('uri'));
//                $group->limit(1);
//                if ($group->find(true)) {
//                    $profile = $group->getProfile();
//                }
//                unset($group);
//            } catch (Exception $e) {
//                Log::error(get_class($e) . ': ' . $e->getMessage());
//                throw $e;
//            }
        }

        if ($profile instanceof Actor) {
            Log::debug(__METHOD__ . ': Found Profile with ID==' . $profile->getID() . ' for resource==' . $resource);
            $target = new WebfingerResourceActor($profile);
            return false;   // We got our target, stop handler execution
        }

        $APNote = ActivitypubActivity::getWithPK(['object_uri' => $resource]);
        if ($APNote instanceof ActivitypubActivity) {
            $target = new WebfingerResource\WebfingerResourceNote(Note::getWithPK(['id' => $APNote->getObjectId()]));
            return false;
        }

        return true;
    }

    public function onStartHostMetaLinks(array &$links)
    {
        foreach (Discovery::supportedMimeTypes() as $type) {
            $links[] = new XML_XRD_Element_Link(Discovery::LRDD_REL,
                Router::url(id: 'freenetwork_webfinger', args: [], type: Router::ABSOLUTE_URL) . '?resource={uri}',
                $type,
                isTemplate: true);
        }

        // TODO OAuth connections
        //$links[] = new XML_XRD_Element_link(self::OAUTH_ACCESS_TOKEN_REL, common_local_url('ApiOAuthAccessToken'));
        //$links[] = new XML_XRD_Element_link(self::OAUTH_REQUEST_TOKEN_REL, common_local_url('ApiOAuthRequestToken'));
        //$links[] = new XML_XRD_Element_link(self::OAUTH_AUTHORIZE_REL, common_local_url('ApiOAuthAuthorize'));
    }

    /**
     * Add a link header for LRDD Discovery
     *
     * @param mixed $action
     */
    public function onStartShowHTML($action)
    {
        if ($action instanceof ShowstreamAction) {
            $resource = $action->getTarget()->getUri();
            $url      = common_local_url('webfinger') . '?resource=' . urlencode($resource);

            foreach ([Discovery::JRD_MIMETYPE, Discovery::XRD_MIMETYPE] as $type) {
                header('Link: <' . $url . '>; rel="' . Discovery::LRDD_REL . '"; type="' . $type . '"', false);
            }
        }
    }

    public function onStartDiscoveryMethodRegistration(Discovery $disco)
    {
        $disco->registerMethod('LRDDMethod_WebFinger');
    }

    public function onEndDiscoveryMethodRegistration(Discovery $disco)
    {
        $disco->registerMethod('LRDDMethod_HostMeta');
        $disco->registerMethod('LRDDMethod_LinkHeader');
        $disco->registerMethod('LRDDMethod_LinkHTML');
    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function onControllerResponseInFormat(string $route, array $accept_header, array $vars, ?TypeResponse &$response = null): bool
    {
        if (!in_array($route, ['freenetwork_hostmeta', 'freenetwork_hostmeta_format', 'freenetwork_webfinger', 'freenetwork_webfinger_format', 'freenetwork_ownerxrd'])) {
            return Event::next;
        }

        $mimeType = array_intersect(array_values(Discovery::supportedMimeTypes()), $accept_header);
        /*
         * "A WebFinger resource MUST return a JRD as the representation
         *  for the resource if the client requests no other supported
         *  format explicitly via the HTTP "Accept" header. [...]
         *  The WebFinger resource MUST silently ignore any requested
         *  representations that it does not understand and support."
         *                                       -- RFC 7033 (WebFinger)
         *                            http://tools.ietf.org/html/rfc7033
         */
        $mimeType = count($mimeType) !== 0 ? array_pop($mimeType) : $vars['default_mimetype'];

        $headers = [];

        if (Common::config('discovery', 'cors')) {
            $headers['Access-Control-Allow-Origin'] = '*';
        }

        $headers['Content-Type'] = $mimeType;

        $response = match ($mimeType) {
            Discovery::XRD_MIMETYPE => new Response(content: $vars['xrd']->to('xml'), headers: $headers),
            Discovery::JRD_MIMETYPE, Discovery::JRD_MIMETYPE_OLD => new JsonResponse(data: $vars['xrd']->to('json'), headers: $headers, json: true),
        };
        return Event::stop;
    }

    /**
     * Fetch all the aliases of some remote profile
     *
     * @param string $uri profile's URI
     *
     * @throws Exception (If the Discovery's HTTP requests fail)
     *
     * @return null|array aliases
     *
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function grab_profile_aliases(string $uri): ?array
    {
        $disco = new Discovery();
        $xrd   = $disco->lookup($uri);

        $all_ids = array_merge([$xrd->subject], $xrd->aliases);

        if (!in_array($uri, $all_ids)) {
            Log::info('The original URI was not listed itself when doing discovery on it!');
            return null;
        }

        return $all_ids;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name'     => 'WebFinger',
            'version'  => self::PLUGIN_VERSION,
            'author'   => 'Mikael Nordfeldth',
            'homepage' => GNUSOCIAL_ENGINE_URL,
            // TRANS: Plugin description.
            'rawdescription' => _m('WebFinger and LRDD support'),
        ];

        return true;
    }
}
