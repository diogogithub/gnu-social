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
 * Entity for the remember_me token
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RememberMeToken
{
    // {{{ Autocode

    private string $series;
    private string $value;
    private DateTimeInterface $lastUsed;
    private string $class;
    private string $username;

    public function setSeries(string $series): self
    {
        $this->series = $series;
        return $this;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setLastUsed(DateTimeInterface $lastUsed): self
    {
        $this->lastUsed = $lastUsed;
        return $this;
    }

    public function getLastUsed(): DateTimeInterface
    {
        return $this->lastUsed;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        $def = [
            'name'   => 'rememberme_token',
            'fields' => [
                'series'   => ['type' => 'char', 'length' => 88, 'not null' => true],
                'value'    => ['type' => 'char', 'length' => 88, 'not null' => true],
                'lastUsed' => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP'],
                'class'    => ['type' => 'varchar', 'length' => 100, 'not null' => true],
                'username' => ['type' => 'varchar', 'length' => 64, 'not null' => true],
            ],
            'primary key' => ['series'],
        ];

        return $def;
    }
}
