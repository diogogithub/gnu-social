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
 * Entity for Notices sources
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
class NoteSource extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $code;
    private string $name;
    private string $url;
    private \DateTimeInterface $modified;

    public function setCode(string $code): self
    {
        $this->code = \mb_substr($code, 0, 32);
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setName(string $name): self
    {
        $this->name = \mb_substr($name, 0, 191);
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setUrl(string $url): self
    {
        $this->url = \mb_substr($url, 0, 191);
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'note_source',
            'fields' => [
                'code'     => ['type' => 'varchar',   'length' => 32,     'not null' => true, 'description' => 'code identifier'],
                'name'     => ['type' => 'varchar',   'length' => 191,    'not null' => true, 'description' => 'name of the source'],
                'url'      => ['type' => 'varchar',   'length' => 191,    'not null' => true, 'description' => 'url to link to'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['code'],
        ];
    }
}
