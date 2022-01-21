<?php

declare(strict_types = 1);

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

/**
 * OAuth2 Client
 *
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2022 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\OAuth2\Entity;

use App\Core\Entity;
use DateTimeInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class Client extends Entity implements ClientEntityInterface
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private string $identifier;
    private string $secret;
    private bool $active;
    private bool $plain_pcke;
    private bool $is_confidential;
    private string $redirect_uris;
    private string $grants;
    private string $scopes;
    private string $client_name;
    private ?string $website = null;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = mb_substr($identifier, 0, 64);
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = mb_substr($secret, 0, 64);
        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setPlainPcke(bool $plain_pcke): self
    {
        $this->plain_pcke = $plain_pcke;
        return $this;
    }

    public function getPlainPcke(): bool
    {
        return $this->plain_pcke;
    }

    public function setIsConfidential(bool $is_confidential): self
    {
        $this->is_confidential = $is_confidential;
        return $this;
    }

    public function getIsConfidential(): bool
    {
        return $this->is_confidential;
    }

    public function setRedirectUris(string $redirect_uris): self
    {
        $this->redirect_uris = $redirect_uris;
        return $this;
    }

    public function getRedirectUris(): string
    {
        return $this->redirect_uris;
    }

    public function setGrants(string $grants): self
    {
        $this->grants = $grants;
        return $this;
    }

    public function getGrants(): string
    {
        return $this->grants;
    }

    public function setScopes(string $scopes): self
    {
        $this->scopes = $scopes;
        return $this;
    }

    public function getScopes(): string
    {
        return $this->scopes;
    }

    public function setClientName(string $client_name): self
    {
        $this->client_name = mb_substr($client_name, 0, 191);
        return $this;
    }

    public function getClientName(): string
    {
        return $this->client_name;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public function getName(): string
    {
        return $this->getClientName();
    }

    public function getRedirectUri(): string
    {
        return mb_substr($this->getRedirectUris(), 0, mb_strpos($this->getRedirectUris(), ' ') ?: null);
    }

    public function isConfidential(): bool
    {
        return false;
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'oauth2_client',
            'fields' => [
                'id'              => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'identifier'      => ['type' => 'char', 'length' => 64, 'not null' => true, 'description' => 'client identifier'],
                'secret'          => ['type' => 'char', 'length' => 64, 'not null' => true, 'description' => 'client secret'],
                'active'          => ['type' => 'bool', 'not null' => true, 'description' => 'whether this client is active'],
                'plain_pcke'      => ['type' => 'bool', 'not null' => true, 'description' => 'whether to allow plaintext PKCE'],
                'is_confidential' => ['type' => 'bool', 'not null' => true, 'description' => 'whether this client needs to provide the secret'],
                'redirect_uris'   => ['type' => 'text', 'not null' => true, 'description' => 'application redirect uris, space separated'],
                'grants'          => ['type' => 'text', 'not null' => true, 'description' => 'application grants, space separated'],
                'scopes'          => ['type' => 'text', 'not null' => true, 'description' => 'application scopes, space separated'],
                'client_name'     => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'name of the application'],
                'website'         => ['type' => 'text', 'not null' => false, 'description' => 'application homepage - used for source link'],
                'created'         => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'        => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
        ];
    }
}
