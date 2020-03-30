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
 * Entity for nonce
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
class Nonce
{
    // {{{ Autocode

    private string $consumer_key;
    private ?string $tok;
    private string $nonce;
    private DateTime $ts;
    private DateTime $created;
    private DateTime $modified;

    public function setConsumerKey(string $consumer_key): self
    {
        $this->consumer_key = $consumer_key;
        return $this;
    }
    public function getConsumerKey(): string
    {
        return $this->consumer_key;
    }

    public function setTok(?string $tok): self
    {
        $this->tok = $tok;
        return $this;
    }
    public function getTok(): ?string
    {
        return $this->tok;
    }

    public function setNonce(string $nonce): self
    {
        $this->nonce = $nonce;
        return $this;
    }
    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function setTs(DateTime $ts): self
    {
        $this->ts = $ts;
        return $this;
    }
    public function getTs(): DateTime
    {
        return $this->ts;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setModified(DateTime $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'nonce',
            'description' => 'OAuth nonce record',
            'fields'      => [
                'consumer_key' => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'unique identifier, root URL'],
                'tok'          => ['type' => 'char', 'length' => 32, 'description' => 'buggy old value, ignored'],
                'nonce'        => ['type' => 'char', 'length' => 32, 'not null' => true, 'description' => 'nonce'],
                'ts'           => ['type' => 'datetime', 'not null' => true, 'description' => 'timestamp sent'],
                'created'      => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['consumer_key', 'ts', 'nonce'],
        ];
    }
}
