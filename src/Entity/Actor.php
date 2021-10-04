<?php

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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Router\Router;
use App\Core\UserRoles;
use App\Util\Exception\NicknameException;
use App\Util\Nickname;
use Component\Avatar\Avatar;
use DateTimeInterface;
use Functional as F;

/**
 * Entity for actors
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Actor extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private string $nickname;
    private ?string $fullname;
    private int $roles = 4;
    private ?string $homepage;
    private ?string $bio;
    private ?string $location;
    private ?float $lat;
    private ?float $lon;
    private ?int $location_id;
    private ?int $location_service;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setFullname(?string $fullname): self
    {
        $this->fullname = $fullname;
        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setRoles(int $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getRoles(): int
    {
        return $this->roles;
    }

    public function setHomepage(?string $homepage): self
    {
        $this->homepage = $homepage;
        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLat(?float $lat): self
    {
        $this->lat = $lat;
        return $this;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLon(?float $lon): self
    {
        $this->lon = $lon;
        return $this;
    }

    public function getLon(): ?float
    {
        return $this->lon;
    }

    public function setLocationId(?int $location_id): self
    {
        $this->location_id = $location_id;
        return $this;
    }

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    public function setLocationService(?int $location_service): self
    {
        $this->location_service = $location_service;
        return $this;
    }

    public function getLocationService(): ?int
    {
        return $this->location_service;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public function getLocalUser()
    {
        return DB::findOneBy('local_user', ['id' => $this->getId()]);
    }

    public function getAvatarUrl(string $size = 'full')
    {
        return Avatar::getAvatarUrl($this->getId(), $size);
    }

    public static function getById(int $id): ?self
    {
        return Cache::get('actor-id-' . $id, function () use ($id) {
            return DB::find('actor', ['id' => $id]);
        });
    }

    public static function getNicknameById(int $id): string
    {
        return Cache::get('actor-nickname-id-' . $id, function () use ($id) {
            return self::getById($id)->getNickname();
        });
    }

    public function getSelfTags(bool $_test_force_recompute = false): array
    {
        return Cache::get('selftags-' . $this->id,
                          fn () => DB::findBy('actor_tag', ['tagger' => $this->id, 'tagged' => $this->id]),
                          beta: $_test_force_recompute ? INF : 1.0);
    }

    public function setSelfTags(array $tags, array $existing): void
    {
        $tag_existing  = F\map($existing, fn ($pt) => $pt->getTag());
        $tag_to_add    = array_diff($tags, $tag_existing);
        $tag_to_remove = array_diff($tag_existing, $tags);
        $pt_to_remove  = F\filter($existing, fn ($pt) => in_array($pt->getTag(), $tag_to_remove));
        foreach ($tag_to_add as $tag) {
            $pt = ActorTag::create(['tagger' => $this->id, 'tagged' => $this->id, 'tag' => $tag]);
            DB::persist($pt);
        }
        foreach ($pt_to_remove as $pt) {
            DB::persist($pt);
            DB::remove($pt);
        }
        Cache::delete('selftags-' . $this->id);
    }

    public function getSubscribersCount()
    {
        return Cache::get('followers-' . $this->id,
                          function () {
                              return DB::dql('select count(f) from App\Entity\Follow f where f.followed = :followed',
                                             ['followed' => $this->id])[0][1] - 1; // Remove self follow
                          });
    }

    public function getSubscriptionsCount()
    {
        return Cache::get('followed-' . $this->id,
                          function () {
                              return DB::dql('select count(f) from App\Entity\Follow f where f.follower = :follower',
                                             ['follower' => $this->id])[0][1] - 1; // Remove self follow
                          });
    }

    public function isPerson(): bool
    {
        return ($this->roles & UserRoles::BOT) === 0;
    }

    /**
     * Resolve an ambiguous nickname reference, checking in following order:
     * - Actors that $sender subscribes to
     * - Actors that subscribe to $sender
     * - Any Actor
     *
     * @param string $nickname validated nickname of
     *
     * @throws NicknameException
     */
    public function findRelativeActor(string $nickname): ?self
    {
        // Will throw exception on invalid input.
        $nickname = Nickname::normalize($nickname, check_already_used: false);
        return Cache::get('relative-nickname-' . $nickname . '-' . $this->getId(),
                          fn () => DB::dql('select a from actor a where ' .
                                           'a.id in (select followed from follow f join actor a on f.followed = a.id where and f.follower = :actor_id and a.nickname = :nickname) or' .
                                           'a.id in (select follower from follow f join actor a on f.follower = a.id where and f.followed = :actor_id and a.nickname = :nickname) or' .
                                           'a.nickname = :nickname' .
                                           'limit 1',
                                           ['nickname' => $nickname, 'actor_id' => $this->getId()]
                          ));
    }

    public function getUri(): string
    {
        return Router::url('actor_id', ['actor_id' => $this->getId()]);
    }

    public function getUrl(): string
    {
        return Router::url('actor_nickname', ['actor_nickname' => $this->getNickname()]);
    }

    public static function schemaDef(): array
    {
        $def = [
            'name'        => 'actor',
            'description' => 'local and remote users, groups and bots are actors, for instance',
            'fields'      => [
                'id'               => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'nickname'         => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'nickname or username'],
                'fullname'         => ['type' => 'text', 'description' => 'display name'],
                'roles'            => ['type' => 'int', 'not null' => true, 'default' => UserRoles::USER, 'description' => 'Bitmap of permissions this actor has'],
                'homepage'         => ['type' => 'text', 'description' => 'identifying URL'],
                'bio'              => ['type' => 'text', 'description' => 'descriptive biography'],
                'location'         => ['type' => 'text', 'description' => 'physical location'],
                'lat'              => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'latitude'],
                'lon'              => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'longitude'],
                'location_id'      => ['type' => 'int', 'description' => 'location id if possible'],
                'location_service' => ['type' => 'int', 'description' => 'service used to obtain location id'],
                'created'          => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'         => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes'     => [
                'actor_nickname_idx' => ['nickname'],
            ],
            'fulltext indexes' => [
                'actor_fulltext_idx' => ['nickname', 'fullname', 'location', 'bio', 'homepage'],
            ],
        ];

        return $def;
    }
}
