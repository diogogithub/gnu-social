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
 * Entity that Keeps a list of unavailable status network names
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
class UnavailableStatusNetwork
{
    // {{{ Autocode

    private string $nickname;
    private \DateTimeInterface $created;

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }
    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'unavailable_status_network',
            'description' => 'An unavailable status network nickname',
            'fields'      => [
                'nickname' => ['type' => 'varchar',
                    'length'          => 64,
                    'not null'        => true, 'description' => 'nickname not to use', ],
                'created' => ['type'  => 'datetime',
                    'not null'        => true, 'default' => '0000-00-00 00:00:00', ],
            ],
            'primary key' => ['nickname'],
        ];
    }
}