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
use App\Core\GSFile;
use App\Core\HTTPClient;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Exception\NoSuchActorException;
use App\Util\Exception\ServerException;
use App\Util\Nickname;
use Component\FreeNetwork\Controller\Feeds;
use Component\FreeNetwork\Controller\HostMeta;
use Component\FreeNetwork\Controller\OwnerXrd;
use Component\FreeNetwork\Controller\Webfinger;
use Component\FreeNetwork\Util\Discovery;
use Component\FreeNetwork\Util\WebfingerResource;
use Component\FreeNetwork\Util\WebfingerResource\WebfingerResourceActor;
use Component\FreeNetwork\Util\WebfingerResource\WebfingerResourceNote;
use Exception;
use const PREG_SET_ORDER;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use XML_XRD;
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
    public const PLUGIN_VERSION = '0.1.0';

    public const OAUTH_ACCESS_TOKEN_REL  = 'http://apinamespace.org/oauth/access_token';
    public const OAUTH_REQUEST_TOKEN_REL = 'http://apinamespace.org/oauth/request_token';
    public const OAUTH_AUTHORIZE_REL     = 'http://apinamespace.org/oauth/authorize';

    public function onAddRoute(RouteLoader $m): bool
    {
        // Feeds
        $m->connect('feed_network', '/feed/network', [Feeds::class, 'network']);
        $m->connect('feed_clique', '/feed/clique', [Feeds::class, 'clique']);
        $m->connect('feed_federated', '/feed/federated', [Feeds::class, 'federated']);

        $m->connect('freenetwork_hostmeta', '.well-known/host-meta', [HostMeta::class, 'handle']);
        $m->connect(
            'freenetwork_hostmeta_format',
            '.well-known/host-meta.:format',
            [HostMeta::class, 'handle'],
            ['format' => '(xml|json)'],
        );
        // the resource GET parameter can be anywhere, so don't mention it here
        $m->connect('freenetwork_webfinger', '.well-known/webfinger', [Webfinger::class, 'handle']);
        $m->connect(
            'freenetwork_webfinger_format',
            '.well-known/webfinger.:format',
            [Webfinger::class, 'handle'],
            ['format' => '(xml|json)'],
        );
        $m->connect('freenetwork_ownerxrd', 'main/ownerxrd', [OwnerXrd::class, 'handle']);
        return Event::next;
    }

    public function onCreateDefaultFeeds(int $actor_id, LocalUser $user, int &$ordering)
    {
        DB::persist(\App\Entity\Feed::create(['actor_id' => $actor_id, 'url' => Router::url($route = 'feed_network'), 'route' => $route, 'title' => _m('Meteorites'), 'ordering' => $ordering++]));
        DB::persist(\App\Entity\Feed::create(['actor_id' => $actor_id, 'url' => Router::url($route = 'feed_clique'), 'route' => $route, 'title' => _m('Planetary System'), 'ordering' => $ordering++]));
        DB::persist(\App\Entity\Feed::create(['actor_id' => $actor_id, 'url' => Router::url($route = 'feed_federated'), 'route' => $route, 'title' => _m('Galaxy'), 'ordering' => $ordering++]));
        return Event::next;
    }

    public function onStartGetProfileAcctUri(Actor $profile, &$acct): bool
    {
        $wfr = new WebFingerResourceActor($profile);
        try {
            $acct = $wfr->reconstructAcct();
        } catch (Exception) {
            return Event::next;
        }

        return Event::stop;
    }

    /**
     * Last attempts getting a WebFingerResource object
     *
     * @param string                 $resource String that contains the requested URI
     * @param null|WebfingerResource $target   WebFingerResource extended object goes here
     * @param array                  $args     Array which may contains arguments such as 'rel' filtering values
     *
     * @throws NicknameEmptyException
     * @throws NicknameException
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     * @throws NoSuchActorException
     * @throws ServerException
     */
    public function onEndGetWebFingerResource(string $resource, ?WebfingerResource &$target = null, array $args = []): bool
    {
        // * Either we didn't find the profile, then we want to make
        //   the $profile variable null for clarity.
        // * Or we did find it but for a possibly malicious remote
        //   user who might've set their profile URL to a Note URL
        //   which would've caused a sort of DoS unless we continue
        //   our search here by discarding the remote profile.
        $profile = null;
        if (Discovery::isAcct($resource)) {
            $parts = explode('@', mb_substr(urldecode($resource), 5)); // 5 is strlen of 'acct:'
            if (\count($parts) === 2) {
                [$nick, $domain] = $parts;
                if ($domain !== $_ENV['SOCIAL_DOMAIN']) {
                    throw new ServerException(_m('Remote profiles not supported via WebFinger yet.'));
                }

                $nick              = Nickname::normalize(nickname: $nick, check_already_used: false, check_is_allowed: false);
                $freenetwork_actor = LocalUser::getByPK(['nickname' => $nick]);
                if (!($freenetwork_actor instanceof LocalUser)) {
                    throw new NoSuchActorException($nick);
                }
                $profile = $freenetwork_actor->getActor();
            }
        } else {
            try {
                if (Common::isValidHttpUrl($resource)) {
                    // This means $resource is a valid url
                    $resource_parts = parse_url($resource);
                    // TODO: Use URLMatcher
                    if ($resource_parts['host'] === $_ENV['SOCIAL_DOMAIN']) { // XXX: Common::config('site', 'server')) {
                        $str = $resource_parts['path'];
                        // actor_view_nickname
                        $renick = '/\/@(' . Nickname::DISPLAY_FMT . ')\/?/m';
                        // actor_view_id
                        $reuri = '/\/actor\/(\d+)\/?/m';
                        if (preg_match_all($renick, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                            $profile = LocalUser::getByPK(['nickname' => $matches[0][1]])->getActor();
                        } elseif (preg_match_all($reuri, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                            $profile = Actor::getById((int) $matches[0][1]);
                        }
                    }
                }
            } catch (NoSuchActorException $e) {
                // not a User, maybe a Note? we'll try that further down...

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
        }

        if ($profile instanceof Actor) {
            Log::debug(__METHOD__ . ': Found Profile with ID==' . $profile->getID() . ' for resource==' . $resource);
            $target = new WebfingerResourceActor($profile);
            return Event::stop; // We got our target, stop handler execution
        }

        if (!\is_null($note = DB::findOneBy(Note::class, ['url' => $resource], return_null: true))) {
            $target = new WebfingerResourceNote($note);
            return Event::stop; // We got our target, stop handler execution
        }

        return Event::next;
    }

    public function onStartHostMetaLinks(array &$links): bool
    {
        foreach (Discovery::supportedMimeTypes() as $type) {
            $links[] = new XML_XRD_Element_Link(
                Discovery::LRDD_REL,
                Router::url(id: 'freenetwork_webfinger', args: [], type: Router::ABSOLUTE_URL) . '?resource={uri}',
                $type,
                isTemplate: true,
            );
        }

        // TODO OAuth connections
        //$links[] = new XML_XRD_Element_link(self::OAUTH_ACCESS_TOKEN_REL, common_local_url('ApiOAuthAccessToken'));
        //$links[] = new XML_XRD_Element_link(self::OAUTH_REQUEST_TOKEN_REL, common_local_url('ApiOAuthRequestToken'));
        //$links[] = new XML_XRD_Element_link(self::OAUTH_AUTHORIZE_REL, common_local_url('ApiOAuthAuthorize'));
        return Event::next;
    }

    /**
     * Add a link header for LRDD Discovery
     */
    public function onStartShowHTML($action): bool
    {
        if ($action instanceof ShowstreamAction) {
            $resource = $action->getTarget()->getUri();
            $url      = common_local_url('webfinger') . '?resource=' . urlencode($resource);

            foreach ([Discovery::JRD_MIMETYPE, Discovery::XRD_MIMETYPE] as $type) {
                header('Link: <' . $url . '>; rel="' . Discovery::LRDD_REL . '"; type="' . $type . '"', false);
            }
        }
        return Event::next;
    }

    public function onStartDiscoveryMethodRegistration(Discovery $disco): bool
    {
        $disco->registerMethod('\Component\FreeNetwork\Util\LrddMethod\LrddMethodWebfinger');
        return Event::next;
    }

    public function onEndDiscoveryMethodRegistration(Discovery $disco): bool
    {
        $disco->registerMethod('\Component\FreeNetwork\Util\LrddMethod\LrddMethodHostMeta');
        $disco->registerMethod('\Component\FreeNetwork\Util\LrddMethod\LrddMethodLinkHeader');
        $disco->registerMethod('\Component\FreeNetwork\Util\LrddMethod\LrddMethodLinkHtml');
        return Event::next;
    }

    /**
     * @throws ClientException
     * @throws ServerException
     */
    public function onControllerResponseInFormat(string $route, array $accept_header, array $vars, ?Response &$response = null): bool
    {
        if (!\in_array($route, ['freenetwork_hostmeta', 'freenetwork_hostmeta_format', 'freenetwork_webfinger', 'freenetwork_webfinger_format', 'freenetwork_ownerxrd'])) {
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
        $mimeType = \count($mimeType) !== 0 ? array_pop($mimeType) : $vars['default_mimetype'];

        $headers = [];

        if (Common::config('discovery', 'cors')) {
            $headers['Access-Control-Allow-Origin'] = '*';
        }

        $headers['Content-Type'] = $mimeType;

        $response = match ($mimeType) {
            Discovery::XRD_MIMETYPE => new Response(content: $vars['xrd']->to('xml'), headers: $headers),
            Discovery::JRD_MIMETYPE, Discovery::JRD_MIMETYPE_OLD => new JsonResponse(data: $vars['xrd']->to('json'), headers: $headers, json: true),
        };

        $response->headers->set('cache-control', 'no-store, no-cache, must-revalidate');

        return Event::stop;
    }

    /**
     * Webfinger matches: @user@example.com or even @user--one.george_orwell@1984.biz
     *
     * @param string $text       The text from which to extract webfinger IDs
     * @param string $preMention Character(s) that signals a mention ('@', '!'...)
     *
     * @return array the matching IDs (without $preMention) and each respective position in the given string
     */
    public static function extractWebfingerIds(string $text, string $preMention = '@'): array
    {
        $wmatches = [];
        $result   = preg_match_all(
            '/' . Nickname::BEFORE_MENTIONS . preg_quote($preMention, '/') . '(' . Nickname::WEBFINGER_FMT . ')/',
            $text,
            $wmatches,
            \PREG_OFFSET_CAPTURE,
        );
        if ($result === false) {
            Log::error(__METHOD__ . ': Error parsing webfinger IDs from text (preg_last_error==' . preg_last_error() . ').');
            return [];
        } elseif (($n_matches = \count($wmatches)) != 0) {
            Log::debug((sprintf('Found %d matches for WebFinger IDs: %s', $n_matches, print_r($wmatches, true))));
        }
        return $wmatches[1];
    }

    /**
     * Profile URL matches: @param string $text The text from which to extract URL mentions
     *
     * @param string $preMention Character(s) that signals a mention ('@', '!'...)
     *
     * @return array the matching URLs (without @ or acct:) and each respective position in the given string
     * @example.com/mublog/user
     */
    public static function extractUrlMentions(string $text, string $preMention = '@'): array
    {
        $wmatches = [];
        // In the regexp below we need to match / _before_ URL_REGEX_VALID_PATH_CHARS because it otherwise gets merged
        // with the TLD before (but / is in URL_REGEX_VALID_PATH_CHARS anyway, it's just its positioning that is important)
        $result = preg_match_all(
            '/' . Nickname::BEFORE_MENTIONS . preg_quote($preMention, '/') . '(' . URL_REGEX_DOMAIN_NAME . '(?:\/[' . URL_REGEX_VALID_PATH_CHARS . ']*)*)/',
            $text,
            $wmatches,
            \PREG_OFFSET_CAPTURE,
        );
        if ($result === false) {
            Log::error(__METHOD__ . ': Error parsing profile URL mentions from text (preg_last_error==' . preg_last_error() . ').');
            return [];
        } elseif (\count($wmatches)) {
            Log::debug((sprintf('Found %d matches for profile URL mentions: %s', \count($wmatches), print_r($wmatches, true))));
        }
        return $wmatches[1];
    }

    /**
     * Find any explicit remote mentions. Accepted forms:
     *   Webfinger: @user@example.com
     *   Profile link: @param Actor $sender
     *
     * @param string $text input markup text
     * @param $mentions
     *
     * @return bool hook return value
     * @example.com/mublog/user
     */
    public function onEndFindMentions(Actor $sender, string $text, array &$mentions): bool
    {
        $matches = [];

        foreach (self::extractWebfingerIds($text, $preMention = '@') as $wmatch) {
            [$target, $pos] = $wmatch;
            Log::info("Checking webfinger person '{$target}'");

            $actor = null;

            $resource_parts = explode($preMention, $target);
            if ($resource_parts[1] === $_ENV['SOCIAL_DOMAIN']) { // XXX: Common::config('site', 'server')) {
                $actor = LocalUser::getByPK(['nickname' => $resource_parts[0]])->getActor();
            } else {
                Event::handle('FreeNetworkFindMentions', [$target, &$actor]);
                if (\is_null($actor)) {
                    continue;
                }
            }
            \assert($actor instanceof Actor);

            $displayName = !empty($actor->getFullname()) ? $actor->getFullname() : $actor->getNickname() ?? $target; // TODO: we could do getBestName() or getFullname() here

            $matches[$pos] = [
                'mentioned' => [$actor],
                'type'      => 'mention',
                'text'      => $displayName,
                'position'  => $pos,
                'length'    => mb_strlen($target),
                'url'       => $actor->getUri(),
            ];
        }

        foreach (self::extractUrlMentions($text) as $wmatch) {
            [$target, $pos] = $wmatch;
            $url            = "https://{$target}";
            if (Common::isValidHttpUrl($url)) {
                // This means $resource is a valid url
                $resource_parts = parse_url($url);
                // TODO: Use URLMatcher
                if ($resource_parts['host'] === $_ENV['SOCIAL_DOMAIN']) { // XXX: Common::config('site', 'server')) {
                    $str = $resource_parts['path'];
                    // actor_view_nickname
                    $renick = '/\/@(' . Nickname::DISPLAY_FMT . ')\/?/m';
                    // actor_view_id
                    $reuri = '/\/actor\/(\d+)\/?/m';
                    if (preg_match_all($renick, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                        $actor = LocalUser::getByPK(['nickname' => $matches[0][1]])->getActor();
                    } elseif (preg_match_all($reuri, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                        $actor = Actor::getById((int) $matches[0][1]);
                    } else {
                        Log::error('Unexpected behaviour onEndFindMentions at FreeNetwork');
                        throw new ServerException('Unexpected behaviour onEndFindMentions at FreeNetwork');
                    }
                } else {
                    Log::info("Checking actor address '{$url}'");

                    $link = new XML_XRD_Element_Link(
                        Discovery::LRDD_REL,
                        'https://' . parse_url($url, \PHP_URL_HOST) . '/.well-known/webfinger?resource={uri}',
                        Discovery::JRD_MIMETYPE,
                        true, // isTemplate
                    );
                    $xrd_uri  = Discovery::applyTemplate($link->template, $url);
                    $response = HTTPClient::get($xrd_uri, ['headers' => ['Accept' => $link->type]]);
                    if ($response->getStatusCode() !== 200) {
                        continue;
                    }

                    $xrd = new XML_XRD();

                    switch (GSFile::mimetypeBare($response->getHeaders()['content-type'][0])) {
                        case Discovery::JRD_MIMETYPE_OLD:
                        case Discovery::JRD_MIMETYPE:
                            $type = 'json';
                            break;
                        case Discovery::XRD_MIMETYPE:
                            $type = 'xml';
                            break;
                        default:
                            // fall back to letting XML_XRD auto-detect
                            Log::debug('No recognized content-type header for resource descriptor body on ' . $xrd_uri);
                            $type = null;
                    }
                    $xrd->loadString($response->getContent(), $type);

                    $actor = null;
                    Event::handle('FreeNetworkFoundXrd', [$xrd, &$actor]);
                    if (\is_null($actor)) {
                        continue;
                    }
                }
                $displayName   = $actor->getFullname() ?? $actor->getNickname() ?? $target; // TODO: we could do getBestName() or getFullname() here
                $matches[$pos] = [
                    'mentioned' => [$actor],
                    'type'      => 'mention',
                    'text'      => $displayName,
                    'position'  => $pos,
                    'length'    => mb_strlen($target),
                    'url'       => $actor->getUri(),
                ];
            }
        }

        foreach ($mentions as $i => $other) {
            // If we share a common prefix with a local user, override it!
            $pos = $other['position'];
            if (isset($matches[$pos])) {
                $mentions[$i] = $matches[$pos];
                unset($matches[$pos]);
            }
        }
        foreach ($matches as $mention) {
            $mentions[] = $mention;
        }

        return Event::next;
    }

    public static function notify(Actor $sender, Activity $activity, array $targets, ?string $reason = null): bool
    {
        $protocols = [];
        Event::handle('AddFreeNetworkProtocol', [&$protocols]);
        $delivered = [];
        foreach ($protocols as $protocol) {
            $protocol::freeNetworkDistribute($sender, $activity, $targets, $reason, $delivered);
        }
        $failed_targets = array_udiff($targets, $delivered, fn (Actor $a, Actor $b): int => $a->getId() <=> $b->getId());
        // TODO: Implement failed queues
        return false;
    }

    public static function mentionToName(string $nickname, string $uri): string
    {
        return '@' . $nickname . '@' . parse_url($uri, \PHP_URL_HOST);
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
