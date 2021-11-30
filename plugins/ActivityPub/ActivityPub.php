<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub;

use App\Core\Event;
use App\Core\HTTPClient;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Exception\NoSuchActorException;
use App\Util\Nickname;
use Exception;
use Plugin\ActivityPub\Controller\Inbox;
use Plugin\ActivityPub\Entity\ActivitypubActor;
use Plugin\ActivityPub\Util\Explorer;
use Plugin\ActivityPub\Util\HTTPSignature;
use Plugin\ActivityPub\Util\Model\EntityToType\EntityToType;
use Plugin\ActivityPub\Util\Response\ActorResponse;
use Plugin\ActivityPub\Util\Response\NoteResponse;
use Plugin\ActivityPub\Util\Response\TypeResponse;
use Plugin\ActivityPub\Util\Type;
use Symfony\Contracts\HttpClient\ResponseInterface;
use XML_XRD;
use XML_XRD_Element_Link;
use function count;
use const PREG_SET_ORDER;

class ActivityPub extends Plugin
{
    // ActivityStreams 2.0 Accept Headers
    public static array $accept_headers = [
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'application/activity+json',
        'application/json',
        'application/ld+json',
    ];

    // So that this isn't hardcoded everywhere
    public const PUBLIC_TO = [
        'https://www.w3.org/ns/activitystreams#Public',
        'Public',
        'as:Public',
    ];
    public const HTTP_CLIENT_HEADERS = [
        'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'User-Agent' => 'GNUsocialBot ' . GNUSOCIAL_VERSION . ' - ' . GNUSOCIAL_PROJECT_URL,
    ];

    public function version(): string
    {
        return '3.0.0';
    }

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our Inbox and Outbox handler for ActivityPub.
     *
     * @param RouteLoader $r the router that was initialized
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect(
            'activitypub_inbox',
            '/inbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]],
        );
        $r->connect(
            'activitypub_actor_inbox',
            '/actor/{gsactor_id<\d+>}/inbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]],
        );
        $r->connect(
            'activitypub_actor_outbox',
            '/actor/{gsactor_id<\d+>}/outbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]],
        );
        return Event::next;
    }

    public function onStartGetActorUrl(Actor $actor, int $type, ?string &$url):bool
    {
        if (
            // Is remote?
            !$actor->getIsLocal()
            // Is in ActivityPub?
            && !is_null($ap_actor = ActivitypubActor::getWithPK(['actor_id' => $actor->getId()]))
            // We can only provide a full URL (anything else wouldn't make sense)
            && $type === Router::ABSOLUTE_URL
        ) {
            $url = $ap_actor->getUri();
            return Event::stop;
        }

        return Event::next;
    }

    public function onAddFreeNetworkProtocol (array &$protocols): bool {
        $protocols[] = '\Plugin\ActivityPub\ActivityPub';
        return Event::next;
    }

    public static function freeNetworkDistribute(Actor $sender, Activity $activity, array $targets, ?string $reason = null): bool
    {
        $to_addr = [];
        foreach($targets as $target) {
            if (is_null($ap_target = ActivitypubActor::getWithPK(['actor_id' => $target->getId()]))) {
                continue;
            }
            $to_addr[$ap_target->getInboxSharedUri() ?? $ap_target->getInboxUri()] = true;
        }

        $errors = [];
        $to_failed = []; // TODO: Implement failed queues
        foreach ($to_addr as $inbox => $dummy) {
            try {
                $res = self::postman($sender, EntityToType::translate($activity), $inbox);

                // accummulate errors for later use, if needed
                $status_code = $res->getStatusCode();
                if (!($status_code === 200 || $status_code === 202 || $status_code === 409)) {
                    $res_body = json_decode($res->getContent(), true);
                    $errors[] = isset($res_body['error']) ?
                        $res_body['error'] : "An unknown error occurred.";
                    $to_failed[$inbox] = $activity;
                }
            } catch (Exception $e) {
                Log::error('ActivityPub @ freeNetworkDistribute: ' . $e->getMessage());
                $to_failed[$inbox] = $activity;
            }

        }

        if (!empty($errors)) {
            Log::error(sizeof($errors) . ' instance/s failed to handle the delete_profile activity!');
            return false;
        }

        return true;
    }

    /**
     * @param Actor $sender
     * @param Type $activity
     * @param string $inbox
     * @param string $method
     * @return ResponseInterface
     */
    public static function postman(Actor $sender, mixed $activity, string $inbox, string $method = 'post'): ResponseInterface
    {
        $data = $activity->toJson();
        Log::debug('ActivityPub Postman: Delivering ' . $data . ' to ' . $inbox);

        $headers = HTTPSignature::sign($sender, $inbox, $data);
        Log::debug('ActivityPub Postman: Delivery headers were: ' . print_r($headers, true));

        $response = HTTPClient::$method($inbox, ['headers' => $headers, 'body' => $data]);
        Log::debug('ActivityPub Postman: Delivery result with status code '.$response->getStatusCode().': '.$response->getContent());
        return $response;
    }


    public static function getActorByUri(string $resource, ?bool $attempt_fetch = true): Actor
    {
        // Try local
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
                    return LocalUser::getWithPK(['nickname' => $matches[0][1]])->getActor();
                } elseif (preg_match_all($reuri, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                    return Actor::getById((int) $matches[0][1]);
                }
            }
        }
        // Try remote
        $aprofile = ActivitypubActor::getByAddr($resource);
        if ($aprofile instanceof ActivitypubActor) {
            return Actor::getById($aprofile->getActorId());
        } else {
            throw new NoSuchActorException("From URI: {$resource}");
        }
    }

    /**
     * @throws Exception
     */
    public function onControllerResponseInFormat(string $route, array $accept_header, array $vars, ?TypeResponse &$response = null): bool
    {
        if (count(array_intersect(self::$accept_headers, $accept_header)) === 0) {
            return Event::next;
        }
        switch ($route) {
            case 'actor_view_id':
            case 'actor_view_nickname':
                $response = ActorResponse::handle($vars['actor']);
                return Event::stop;
            case 'note_view':
                $response = NoteResponse::handle($vars['note']);
                return Event::stop;
            /*case 'actor_favourites_id':
            case 'actor_favourites_nickname':
                $response = LikeResponse::handle($vars['actor']);
                return Event::stop;
            case 'actor_subscriptions_id':
            case 'actor_subscriptions_nickname':
                $response = FollowingResponse::handle($vars['actor']);
                return Event::stop;
            case 'actor_subscribers_id':
            case 'actor_subscribers_nickname':
                $response = FollowersResponse::handle($vars['actor']);
                return Event::stop;*/
            default:
                if (Event::handle('ActivityStreamsTwoResponse', [$route, &$response]) == Event::stop) {
                    return Event::stop;
                }
                return Event::next;
        }
    }

    /********************************************************
     *                   WebFinger Events                   *
     ********************************************************/

    /**
     * Add activity+json mimetype on WebFinger
     *
     * @throws Exception
     */
    public function onEndWebFingerProfileLinks(XML_XRD $xrd, Actor $object): bool
    {
        if ($object->isPerson()) {
            $link = new XML_XRD_Element_Link(
                rel: 'self',
                href: $object->getUri(Router::ABSOLUTE_URL),//Router::url('actor_view_id', ['id' => $object->getId()], Router::ABSOLUTE_URL),
                type: 'application/activity+json',
            );
            $xrd->links[] = clone $link;
        }
        return Event::next;
    }

    /**
     * Webfinger matches: @user@example.com or even @user--one.george_orwell@1984.biz
     *
     * @param   string  $text       The text from which to extract webfinger IDs
     * @param   string  $preMention Character(s) that signals a mention ('@', '!'...)
     * @return  array   The matching IDs (without $preMention) and each respective position in the given string.
     */
    public static function extractWebfingerIds(string $text, string $preMention='@'): array
    {
        $wmatches = [];
        $result = preg_match_all(
            '/'.Nickname::BEFORE_MENTIONS.preg_quote($preMention, '/').'('.Nickname::WEBFINGER_FMT.')/',
            $text,
            $wmatches,
            PREG_OFFSET_CAPTURE
        );
        if ($result === false) {
            Log::error(__METHOD__ . ': Error parsing webfinger IDs from text (preg_last_error=='.preg_last_error().').');
            return [];
        } elseif (($n_matches = count($wmatches)) != 0) {
            Log::debug((sprintf('Found %d matches for WebFinger IDs: %s', $n_matches, print_r($wmatches, true))));
        }
        return $wmatches[1];
    }

    /**
     * Profile URL matches: @example.com/mublog/user
     *
     * @param   string  $text       The text from which to extract URL mentions
     * @param   string  $preMention Character(s) that signals a mention ('@', '!'...)
     * @return  array   The matching URLs (without @ or acct:) and each respective position in the given string.
     */
    public static function extractUrlMentions(string $text, string $preMention='@'): array
    {
        $wmatches = [];
        // In the regexp below we need to match / _before_ URL_REGEX_VALID_PATH_CHARS because it otherwise gets merged
        // with the TLD before (but / is in URL_REGEX_VALID_PATH_CHARS anyway, it's just its positioning that is important)
        $result = preg_match_all(
            '/(?:^|\s+)'.preg_quote($preMention, '/').'('.URL_REGEX_DOMAIN_NAME.'(?:\/['.URL_REGEX_VALID_PATH_CHARS.']*)*)/',
            $text,
            $wmatches,
            PREG_OFFSET_CAPTURE
        );
        if ($result === false) {
            Log::error(__METHOD__ . ': Error parsing profile URL mentions from text (preg_last_error=='.preg_last_error().').');
            return [];
        } elseif (count($wmatches)) {
            Log::debug((sprintf('Found %d matches for profile URL mentions: %s', count($wmatches), print_r($wmatches, true))));
        }
        return $wmatches[1];
    }

    /**
     * Find any explicit remote mentions. Accepted forms:
     *   Webfinger: @user@example.com
     *   Profile link: @example.com/mublog/user
     * @param Actor $sender
     * @param string $text input markup text
     * @param $mentions
     * @return bool hook return value
     */
    public function onEndFindMentions(Actor $sender, string $text, array &$mentions): bool
    {
        $matches = [];

        foreach (self::extractWebfingerIds($text, '@') as $wmatch) {
            list($target, $pos) = $wmatch;
            Log::info("Checking webfinger person '$target'");
            $profile = null;
            try {
                $aprofile = ActivitypubActor::getByAddr($target);
                $profile = Actor::getById($aprofile->getActorId());
            } catch (Exception $e) {
                Log::error("Webfinger check failed: " . $e->getMessage());
                continue;
            }
            assert($profile instanceof Actor);

            $displayName = $profile->getFullname() ?? $profile->getNickname() ?? $target; // TODO: we could do getBestName() or getFullname() here

            $matches[$pos] = [
                'mentioned' => [$profile],
                'type' => 'mention',
                'text' => $displayName,
                'position' => $pos,
                'length' => mb_strlen($target),
                'url' => $aprofile->getUri()
            ];
        }

        foreach (self::extractUrlMentions($text) as $wmatch) {
            list($target, $pos) = $wmatch;
            $schemes = array('https', 'http');
            foreach ($schemes as $scheme) {
                $url = "$scheme://$target";
                Log::info("Checking profile address '$url'");
                try {
                    $aprofile = ActivitypubActor::fromUri($url);
                    $profile = Actor::getById($aprofile->getActorId());
                    $displayName = $profile->getFullname() ?? $profile->getNickname() ?? $target; // TODO: we could do getBestName() or getFullname() here
                    $matches[$pos] = ['mentioned' => [$profile],
                        'type' => 'mention',
                        'text' => $displayName,
                        'position' => $pos,
                        'length' => mb_strlen($target),
                        'url' => $aprofile->getUri()
                    ];
                    break;
                } catch (Exception $e) {
                    Log::error("Profile check failed: " . $e->getMessage());
                }
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

    /**
     * Allow remote profile references to be used in commands:
     *   sub update@status.net
     *   whois evan@identi.ca
     *   reply http://identi.ca/evan hey what's up
     *
     * @param Command $command
     * @param string $arg
     * @param Actor &$profile
     * @return bool hook return code
     * @author GNU social
     */
    //public function onStartCommandGetProfile($command, $arg, &$profile)
    //{
    //    $aprofile = ActivitypubActor::fromUri($arg);
    //    if (!($aprofile instanceof ActivitypubActor)) {
    //        // No remote ActivityPub profile found
    //        return Event::next;
    //    }
    //
    //    return Event::stop;
    //}

    /********************************************************
     *                   Discovery Events                   *
     ********************************************************/

    /**
     * Profile from URI.
     *
     * @author GNU social
     * @param string $uri
     * @param Actor &$profile in/out param: Profile got from URI
     * @return mixed hook return code
     */
    //public function onStartGetProfileFromURI($uri, &$profile)
    //{
    //    try {
    //        $profile = Explorer::get_profile_from_url($uri);
    //        return Event::stop;
    //    } catch (Exception) {
    //        return Event::next; // It's not an ActivityPub profile as far as we know, continue event handling
    //    }
    //}

    /**
     * Try to grab and store the remote profile by the given uri
     *
     * @param string $uri
     * @param Actor|null &$profile
     * @return bool
     */
    //public function onRemoteFollowPullProfile(string $uri, ?Actor &$profile): bool
    //{
    //    $aprofile = ActivitypubActor::fromUri($uri);
    //    if (!($aprofile instanceof ActivitypubActor)) {
    //        // No remote ActivityPub profile found
    //        return Event::next;
    //    }
    //
    //    return is_null($profile) ? Event::next : Event::stop;
    //}
}
