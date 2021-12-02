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

/**
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Entity;

use App\Core\Cache;
use App\Core\Entity;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Entity\Actor;
use Component\FreeNetwork\Util\Discovery;
use DateTimeInterface;
use Exception;
use Plugin\ActivityPub\Util\DiscoveryHints;
use Plugin\ActivityPub\Util\Explorer;

/**
 * Table Definition for activitypub_actor
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivitypubActor extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $uri;
    private int $actor_id;
    private string $inbox_uri;
    private ?string $inbox_shared_uri = null;
    private string $url;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getInboxUri(): string
    {
        return $this->inbox_uri;
    }

    public function setInboxUri(string $inbox_uri): self
    {
        $this->inbox_uri = $inbox_uri;
        return $this;
    }

    public function getInboxSharedUri(): ?string
    {
        return $this->inbox_shared_uri;
    }

    public function setInboxSharedUri(?string $inbox_shared_uri = null): self
    {
        $this->inbox_shared_uri = $inbox_shared_uri;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    // @codeCoverageIgnoreEnd
    // }}} Autocode

    /**
     * Look up, and if necessary create, an Activitypub_profile for the remote
     * entity with the given WebFinger address.
     * This should never return null -- you will either get an object or
     * an exception will be thrown.
     *
     * @param string $addr WebFinger address
     *
     * @throws Exception on error conditions
     */
    public static function getByAddr(string $addr): self
    {
        // Normalize $addr, i.e. add 'acct:' if missing
        $addr = Discovery::normalize($addr);

        // Try the cache
        $uri = Cache::get(sprintf('ActivitypubActor-webfinger-%s', urlencode($addr)), fn () => false);

        if ($uri !== false) {
            if (\is_null($uri)) {
                // TRANS: Exception.
                throw new Exception(_m('Not a valid WebFinger address (via cache).'));
            }
            try {
                return self::fromUri($uri);
            } catch (Exception $e) {
                Log::error(sprintf(__METHOD__ . ': WebFinger address cache inconsistent with database, did not find Activitypub_profile uri==%s', $uri));
                Cache::set(sprintf('ActivitypubActor-webfinger-%s', urlencode($addr)), false);
            }
        }

        // Now, try some discovery

        $disco = new Discovery();

        try {
            $xrd = $disco->lookup($addr);
        } catch (Exception $e) {
            // Save negative cache entry so we don't waste time looking it up again.
            // @todo FIXME: Distinguish temporary failures?
            Cache::set(sprintf('ActivitypubActor-webfinger-%s', urlencode($addr)), null);
            // TRANS: Exception.
            throw new Exception(_m('Not a valid WebFinger address: ' . $e->getMessage()));
        }

        $hints = array_merge(
            ['webfinger' => $addr],
            DiscoveryHints::fromXRD($xrd),
        );

        if (\array_key_exists('activitypub', $hints)) {
            $uri = $hints['activitypub'];
            try {
                LOG::info("Discovery on acct:{$addr} with URI:{$uri}");
                $aprofile = self::fromUri($hints['activitypub']);
                Cache::set(sprintf('ActivitypubActor-webfinger-%s', urlencode($addr)), $aprofile->getUri());
                return $aprofile;
            } catch (Exception $e) {
                Log::warning("Failed creating profile from URI:'{$uri}', error:" . $e->getMessage());
                throw $e;
                // keep looking
                //
                // @todo FIXME: This means an error discovering from profile page
                // may give us a corrupt entry using the webfinger URI, which
                // will obscure the correct page-keyed profile later on.
            }
        }

        // XXX: try hcard
        // XXX: try FOAF

        // TRANS: Exception. %s is a WebFinger address.
        throw new Exception(sprintf(_m('Could not find a valid profile for "%s".'), $addr));
    }

    /**
     * Ensures a valid Activitypub_profile when provided with a valid URI.
     *
     * @param bool $grab_online whether to try online grabbing, defaults to true
     *
     * @throws Exception if it isn't possible to return an Activitypub_profile
     */
    public static function fromUri(string $url, bool $grab_online = true): self
    {
        try {
            return Explorer::get_profile_from_url($url, $grab_online);
        } catch (Exception $e) {
            throw new Exception('No valid ActivityPub profile found for given URI.', previous: $e);
        }
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'activitypub_actor',
            'fields' => [
                'uri'              => ['type' => 'text', 'not null' => true],
                'actor_id'         => ['type' => 'int', 'not null' => true],
                'inbox_uri'        => ['type' => 'text', 'not null' => true],
                'inbox_shared_uri' => ['type' => 'text'],
                'url'              => ['type' => 'text'],
                'created'          => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'         => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['actor_id'],
            'foreign keys' => [
                'activitypub_actor_actor_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
        ];
    }
}
