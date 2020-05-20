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

use DateTimeInterface;

/**
 * Entity for Notice's location
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
class NoticeLocation
{
    // {{{ Autocode

    private int $notice_id;
    private ?float $lat;
    private ?float $lon;
    private ?int $location_id;
    private ?int $location_ns;
    private DateTimeInterface $modified;

    public function setNoticeId(int $notice_id): self
    {
        $this->notice_id = $notice_id;
        return $this;
    }

    public function getNoticeId(): int
    {
        return $this->notice_id;
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

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'notice_location',
            'fields' => [
                'notice_id'   => ['type' => 'int', 'not null' => true, 'description' => 'notice that is the reply'],
                'lat'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'latitude'],
                'lon'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'longitude'],
                'location_id' => ['type' => 'int', 'description' => 'location id if possible'],
                'location_ns' => ['type' => 'int', 'description' => 'namespace for location'],
                'modified'    => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['notice_id'],
            'foreign keys' => [
                'notice_location_notice_id_fkey' => ['notice', ['notice_id' => 'id']],
            ],
            'indexes' => [
                'notice_location_location_id_idx' => ['location_id'],
            ],
        ];
    }
}