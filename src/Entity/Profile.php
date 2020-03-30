<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for user profiles
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Profile
{
    // {{{ Autocode

    private int $id;
    private string $nickname;
    private ?string $fullname;
    private ?string $profileurl;
    private ?string $homepage;
    private ?string $bio;
    private ?string $location;
    private ?float $lat;
    private ?float $lon;
    private ?int $location_id;
    private ?int $location_ns;
    private DateTime $created;
    private DateTime $modified;

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

    public function setProfileurl(?string $profileurl): self
    {
        $this->profileurl = $profileurl;
        return $this;
    }
    public function getProfileurl(): ?string
    {
        return $this->profileurl;
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

    public function setLocationNs(?int $location_ns): self
    {
        $this->location_ns = $location_ns;
        return $this;
    }
    public function getLocationNs(): ?int
    {
        return $this->location_ns;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setModified(DateTime $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        $def = [
            'name'        => 'profile',
            'description' => 'local and remote users have profiles',
            'fields'      => [
                'id'          => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'nickname'    => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'nickname or username', 'collate' => 'utf8mb4_general_ci'],
                'fullname'    => ['type' => 'text', 'description' => 'display name', 'collate' => 'utf8mb4_general_ci'],
                'profileurl'  => ['type' => 'text', 'description' => 'URL, cached so we dont regenerate'],
                'homepage'    => ['type' => 'text', 'description' => 'identifying URL', 'collate' => 'utf8mb4_general_ci'],
                'bio'         => ['type' => 'text', 'description' => 'descriptive biography', 'collate' => 'utf8mb4_general_ci'],
                'location'    => ['type' => 'text', 'description' => 'physical location', 'collate' => 'utf8mb4_general_ci'],
                'lat'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'latitude'],
                'lon'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'longitude'],
                'location_id' => ['type' => 'int', 'description' => 'location id if possible'],
                'location_ns' => ['type' => 'int', 'description' => 'namespace for location'],
                'created'     => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes'     => [
                'profile_nickname_idx' => ['nickname'],
            ],
        ];

        // TODO
        // if (common_config('search', 'type') == 'fulltext') {
        //     $def['fulltext indexes'] = ['nickname' => ['nickname', 'fullname', 'location', 'bio', 'homepage']];
        // }

        return $def;
    }
}
