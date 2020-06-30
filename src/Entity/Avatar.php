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
 * Entity for user's avatar
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
class Avatar
{
    // {{{ Autocode

    private int $profile_id;
    private ?bool $is_original;
    private int $width;
    private int $height;
    private string $mediatype;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setProfileId(int $profile_id): self
    {
        $this->profile_id = $profile_id;
        return $this;
    }
    public function getProfileId(): int
    {
        return $this->profile_id;
    }

    public function setIsOriginal(?bool $is_original): self
    {
        $this->is_original = $is_original;
        return $this;
    }
    public function getIsOriginal(): ?bool
    {
        return $this->is_original;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }
    public function getWidth(): int
    {
        return $this->width;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }
    public function getHeight(): int
    {
        return $this->height;
    }

    public function setMediatype(string $mediatype): self
    {
        $this->mediatype = $mediatype;
        return $this;
    }
    public function getMediatype(): string
    {
        return $this->mediatype;
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

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'avatar',
            'fields' => [
                'profile_id'  => ['type' => 'int', 'not null' => true,  'description' => 'foreign key to profile table'],
                'is_original' => ['type' => 'bool', 'default' => false, 'description' => 'uploaded by user or generated?'],
                'width'       => ['type' => 'int', 'not null' => true,  'description' => 'image width'],
                'height'      => ['type' => 'int', 'not null' => true,  'description' => 'image height'],
                'mediatype'   => ['type' => 'varchar', 'length' => 32,  'not null' => true, 'description' => 'file type'],
                'created'     => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['profile_id', 'width', 'height'],
            'foreign keys' => [
                'avatar_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
            ],
            'indexes' => [
                'avatar_profile_id_idx' => ['profile_id'],
            ],
        ];
    }
}
