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
 * Entity for user's email confimation
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
class ConfirmAddress
{
    // {{{ Autocode

    private string $code;
    private ?int $user_id;
    private string $address;
    private ?string $address_extra;
    private string $address_type;
    private ?\DateTimeInterface $claimed;
    private ?\DateTimeInterface $sent;
    private \DateTimeInterface $modified;

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }
    public function getCode(): string
    {
        return $this->code;
    }

    public function setUserId(?int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }
    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddressExtra(?string $address_extra): self
    {
        $this->address_extra = $address_extra;
        return $this;
    }
    public function getAddressExtra(): ?string
    {
        return $this->address_extra;
    }

    public function setAddressType(string $address_type): self
    {
        $this->address_type = $address_type;
        return $this;
    }
    public function getAddressType(): string
    {
        return $this->address_type;
    }

    public function setClaimed(?DateTimeInterface $claimed): self
    {
        $this->claimed = $claimed;
        return $this;
    }
    public function getClaimed(): ?DateTimeInterface
    {
        return $this->claimed;
    }

    public function setSent(?DateTimeInterface $sent): self
    {
        $this->sent = $sent;
        return $this;
    }
    public function getSent(): ?DateTimeInterface
    {
        return $this->sent;
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
            'name'   => 'confirm_address',
            'fields' => [
                'code'          => ['type' => 'varchar',  'length' => 32, 'not null' => true, 'description' => 'good random code'],
                'user_id'       => ['type' => 'int',      'default' => 0, 'description' => 'user who requested confirmation'],
                'address'       => ['type' => 'varchar',  'length' => 191, 'not null' => true, 'description' => 'address (email, xmpp, SMS, etc.)'],
                'address_extra' => ['type' => 'varchar',  'length' => 191, 'description' => 'carrier ID, for SMS'],
                'address_type'  => ['type' => 'varchar',  'length' => 8, 'not null' => true, 'description' => 'address type ("email", "xmpp", "sms")'],
                'claimed'       => ['type' => 'datetime', 'description' => 'date this was claimed for queueing'],
                'sent'          => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this was sent for queueing'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['code'],
            'foreign keys' => [
                'confirm_address_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
        ];
    }
}
