<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
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
 * Entity for File redirects
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
class FileRedirection
{
    // {{{ Autocode

    private string $urlhash;
    private ?string $url;
    private ?int $file_id;
    private ?int $redirections;
    private ?int $httpcode;
    private DateTimeInterface $modified;

    public function setUrlhash(string $urlhash): self
    {
        $this->urlhash = $urlhash;
        return $this;
    }
    public function getUrlhash(): string
    {
        return $this->urlhash;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }
    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setFileId(?int $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }
    public function getFileId(): ?int
    {
        return $this->file_id;
    }

    public function setRedirections(?int $redirections): self
    {
        $this->redirections = $redirections;
        return $this;
    }
    public function getRedirections(): ?int
    {
        return $this->redirections;
    }

    public function setHttpcode(?int $httpcode): self
    {
        $this->httpcode = $httpcode;
        return $this;
    }
    public function getHttpcode(): ?int
    {
        return $this->httpcode;
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
            'name'   => 'file_redirection',
            'fields' => [
                'urlhash'      => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'sha256 hash of the URL'],
                'url'          => ['type' => 'text', 'description' => 'short URL (or any other kind of redirect) for file (id)'],
                'file_id'      => ['type' => 'int', 'description' => 'short URL for what URL/file'],
                'redirections' => ['type' => 'int', 'description' => 'redirect count'],
                'httpcode'     => ['type' => 'int', 'description' => 'HTTP status code (20x, 30x, etc.)'],
                'modified'     => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['urlhash'],
            'foreign keys' => [
                'file_redirection_file_id_fkey' => ['file', ['file_id' => 'id']],
            ],
        ];
    }
}