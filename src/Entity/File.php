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
 * Entity for uploaded files
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
class File
{
    // {{{ Autocode

    private int $id;
    private string $urlhash;
    private ?string $url;
    private ?string $filehash;
    private ?string $mimetype;
    private ?int $size;
    private ?string $title;
    private ?int $date;
    private ?int $protected;
    private ?string $filename;
    private ?int $width;
    private ?int $height;
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

    public function setFilehash(?string $filehash): self
    {
        $this->filehash = $filehash;
        return $this;
    }
    public function getFilehash(): ?string
    {
        return $this->filehash;
    }

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = $mimetype;
        return $this;
    }
    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }
    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDate(?int $date): self
    {
        $this->date = $date;
        return $this;
    }
    public function getDate(): ?int
    {
        return $this->date;
    }

    public function setProtected(?int $protected): self
    {
        $this->protected = $protected;
        return $this;
    }
    public function getProtected(): ?int
    {
        return $this->protected;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;
        return $this;
    }
    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;
        return $this;
    }
    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'file',
            'fields' => [
                'id'        => ['type' => 'serial', 'not null' => true],
                'urlhash'   => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'sha256 of destination URL (url field)'],
                'url'       => ['type' => 'text', 'description' => 'destination URL after following possible redirections'],
                'filehash'  => ['type' => 'varchar', 'length' => 64, 'not null' => false, 'description' => 'sha256 of the file contents, only for locally stored files of course'],
                'mimetype'  => ['type' => 'varchar', 'length' => 50, 'description' => 'mime type of resource'],
                'size'      => ['type' => 'int', 'description' => 'size of resource when available'],
                'title'     => ['type' => 'text', 'description' => 'title of resource when available'],
                'date'      => ['type' => 'int', 'description' => 'date of resource according to http query'],
                'protected' => ['type' => 'int', 'description' => 'true when URL is private (needs login)'],
                'filename'  => ['type' => 'text', 'description' => 'if file is stored locally (too) this is the filename'],
                'width'     => ['type' => 'int', 'description' => 'width in pixels, if it can be described as such and data is available'],
                'height'    => ['type' => 'int', 'description' => 'height in pixels, if it can be described as such and data is available'],
                'modified'  => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'file_urlhash_key' => ['urlhash'],
            ],
            'indexes' => [
                'file_filehash_idx' => ['filehash'],
            ],
        ];
    }
}
