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

use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for user's url shortener preferences
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
class UserUrlShortenerPrefs extends Entity
{
    // {{{ Autocode

    private int $user_id;
    private ?string $url_shortening_service;
    private int $max_url_length;
    private int $max_notice_length;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUrlShorteningService(?string $url_shortening_service): self
    {
        $this->url_shortening_service = $url_shortening_service;
        return $this;
    }

    public function getUrlShorteningService(): ?string
    {
        return $this->url_shortening_service;
    }

    public function setMaxUrlLength(int $max_url_length): self
    {
        $this->max_url_length = $max_url_length;
        return $this;
    }

    public function getMaxUrlLength(): int
    {
        return $this->max_url_length;
    }

    public function setMaxNoticeLength(int $max_notice_length): self
    {
        $this->max_notice_length = $max_notice_length;
        return $this;
    }

    public function getMaxNoticeLength(): int
    {
        return $this->max_notice_length;
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
            'name'   => 'user_url_shortener_prefs',
            'fields' => [
                'user_id'                => ['type' => 'int', 'not null' => true, 'description' => 'user'],
                'url_shortening_service' => ['type' => 'varchar', 'length' => 50, 'default' => 'internal', 'description' => 'service to use for auto-shortening URLs'],
                'max_url_length'         => ['type' => 'int', 'not null' => true, 'description' => 'urls greater than this length will be shortened, 0 = always, -1 = never'],
                'max_notice_length'      => ['type' => 'int', 'not null' => true, 'description' => 'notices with content greater than this value will have all urls shortened, 0 = always, -1 = only if notice text is longer than max allowed'],
                'created'                => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'               => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['user_id'],
            'foreign keys' => [
                'user_urlshortener_prefs_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
        ];
    }
}
