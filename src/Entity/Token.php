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
 * Entity for User token
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
class Token
{
    // {{{ Autocode

    private string $consumer_key;
    private string $tok;
    private string $secret;
    private int $type;
    private ?int $state;
    private ?string $verifier;
    private ?string $verified_callback;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setConsumerKey(string $consumer_key): self
    {
        $this->consumer_key = $consumer_key;
        return $this;
    }
    public function getConsumerKey(): string
    {
        return $this->consumer_key;
    }

    public function setTok(string $tok): self
    {
        $this->tok = $tok;
        return $this;
    }
    public function getTok(): string
    {
        return $this->tok;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;
        return $this;
    }
    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setType(int $type): self
    {
        $this->type = $type;
        return $this;
    }
    public function getType(): int
    {
        return $this->type;
    }

    public function setState(?int $state): self
    {
        $this->state = $state;
        return $this;
    }
    public function getState(): ?int
    {
        return $this->state;
    }

    public function setVerifier(?string $verifier): self
    {
        $this->verifier = $verifier;
        return $this;
    }
    public function getVerifier(): ?string
    {
        return $this->verifier;
    }

    public function setVerifiedCallback(?string $verified_callback): self
    {
        $this->verified_callback = $verified_callback;
        return $this;
    }
    public function getVerifiedCallback(): ?string
    {
        return $this->verified_callback;
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
            'name'        => 'token',
            'description' => 'OAuth token record',
            'fields'      => [
                'consumer_key'      => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'unique identifier, root URL'],
                'tok'               => ['type' => 'char', 'length' => 32, 'not null' => true, 'description' => 'identifying value'],
                'secret'            => ['type' => 'char', 'length' => 32, 'not null' => true, 'description' => 'secret value'],
                'type'              => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 0, 'description' => 'request or access'],
                'state'             => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'for requests, 0 = initial, 1 = authorized, 2 = used'],
                'verifier'          => ['type' => 'varchar', 'length' => 191, 'description' => 'verifier string for OAuth 1.0a'],
                'verified_callback' => ['type' => 'varchar', 'length' => 191, 'description' => 'verified callback URL for OAuth 1.0a'],
                'created'           => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'          => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['consumer_key', 'tok'],
            'foreign keys' => [
                'token_consumer_key_fkey' => ['consumer', ['consumer_key' => 'consumer_key']],
            ],
        ];
    }
}
